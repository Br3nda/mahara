<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2008 Catalyst IT Ltd (http://www.catalyst.net.nz)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage artefact-file-import-leap
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

/**
 * Implements LEAP2A import of file/folder related entries into Mahara
 *
 * For more information about LEAP file importing, see:
 * http://wiki.mahara.org/Developer_Area/Import%2f%2fExport/LEAP_Import/File_Artefact_Plugin
 *
 * TODO:
 * - Protect get_children_of_folder against circular references
 */
class LeapImportFile extends LeapImportArtefactPlugin {

    /**
     * Import an entry as a file
     */
    const STRATEGY_IMPORT_AS_FILE = 1;

    /**
     * Import an entry as a folder, using any children folders and files
     */
    const STRATEGY_IMPORT_AS_FOLDER = 2;

    public static function get_import_strategies_for_entry(SimpleXMLElement $entry, PluginImport $importer) {
        $strategies = array();

        if (!self::has_parent_folder($entry, $importer)) {
            if (self::is_file($entry, $importer)) {
                // We import these into the top level directory of a user's 'My 
                // Files' area
                $strategies[] = array(
                    'strategy' => self::STRATEGY_IMPORT_AS_FILE,
                    'score'    => 100,
                    'other_required_entries' => array(),
                );
            }
            else if (self::is_folder($entry, $importer)) {
                // It's a folder with no parent. We import these into the top level 
                // directory, using all the files/folders under it to do so
                $strategies[] = array(
                    'strategy' => self::STRATEGY_IMPORT_AS_FOLDER,
                    'score'    => 100,
                    'other_required_entries' => self::get_children_of_folder($entry, $importer, true),
                );
            }
        }

        return $strategies;
    }

    // TODO: we're assuming an empty files area to work with, but that might 
    // not be the case, in which case we have conflicting file/folder names to 
    // deal with!
    public static function import_using_strategy(SimpleXMLElement $entry, PluginImport $importer, $strategy, array $otherentries) {
        $artefactmapping = array();
        switch ($strategy) {
        case self::STRATEGY_IMPORT_AS_FILE:
            $artefactmapping[(string)$entry->id] = array(self::create_file($entry, $importer)->get('id'));
            break;
        case self::STRATEGY_IMPORT_AS_FOLDER:
            $artefactmapping = self::create_folder_and_children($entry, $importer);
            break;
        default:
            throw new ImportException($importer, 'TODO: get_string: unknown strategy chosen for importing entry');
        }
        return $artefactmapping;
    }

    /**
     * Returns whether the given entry is a file
     *
     * We consider an entry to be a file if it has its content out of line, and 
     * if it's of rdf:type rdf:resource. This may be more strict than necessary 
     * - possibly just having the content ouf of line should be enough.
     *
     * @param SimpleXMLElement $entry The entry to check
     * @param PluginImport $importer  The importer
     * @return boolean Whether the entry is a file
     */
    private static function is_file(SimpleXMLElement $entry, PluginImport $importer) {
        $correctrdftype = count($entry->xpath('rdf:type['
            . $importer->curie_xpath('@rdf:resource', PluginImportLeap::NS_LEAPTYPE, 'resource') . ']')) == 1;
        $outoflinecontent = isset($entry->content['src']);
        return $correctrdftype && $outoflinecontent;
    }

    /**
     * Returns whether the given entry is a folder
     *
     * @param SimpleXMLElement $entry The entry to check
     * @param PluginImport $importer  The importer
     * @return boolean Whether the entry is a folder
     */
    private static function is_folder(SimpleXMLElement $entry, PluginImport $importer) {
        static $cache = array();
        $id = (string)$entry->id;
        if (isset($cache[$id])) {
            return $cache[$id];
        }
        $correctrdftype = count($entry->xpath('rdf:type['
            . $importer->curie_xpath('@rdf:resource', PluginImportLeap::NS_LEAPTYPE, 'selection') . ']')) == 1;
        $correctcategoryscheme = count($entry->xpath('a:category[('
            . $importer->curie_xpath('@scheme', PluginImportLeap::NS_CATEGORIES, 'selection_type#') . ') and @term="Folder"]')) == 1;
        return ($cache[$id] = $correctrdftype && $correctcategoryscheme);
    }

    /**
     * Returns whether the given entry considers itself "part of" a folder - 
     * i.e., whether it's in a folder.
     *
     * The entry itself can be any entry, although in the context of this 
     * plugin, it is a file or folder.
     *
     * @param SimpleXMLElement $entry The entry to check
     * @param PluginImport $importer  The importer
     * @return boolean Whether this entry is in a folder
     */
    private static function has_parent_folder(SimpleXMLElement $entry, PluginImport $importer) {
        foreach ($entry->link as $link) {
            if ($importer->curie_equals($link['rel'], PluginImportLeap::NS_LEAP, 'is_part_of') && isset($link['href'])) {
                $potentialfolder = $importer->get_entry_by_id((string)$link['href']);
                if ($potentialfolder && self::is_folder($potentialfolder, $importer)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns a list of entry IDs that are children of this folder
     *
     * If necessary, this method can act recursively to find children at all 
     * levels under this folder
     *
     * TODO: protection against circular references
     *
     * @param SimpleXMLElement $entry The folder to get children for
     * @param PluginImport $importer  The importer
     * @param boolean $recurse        Whether to return children at all levels below this folder
     * @return array A list of the entry IDs of children in this folder
     */
    private static function get_children_of_folder(SimpleXMLElement $entry, PluginImport $importer, $recurse=false) {
        $children = array();

        // Get entries that this folder feels are a part of it
        foreach ($entry->link as $link) {
            if ($importer->curie_equals($link['rel'], PluginImportLeap::NS_LEAP, 'has_part') && isset($link['href'])) {
                $child = $importer->get_entry_by_id((string)$link['href']);
                if ($child) {
                    if (self::is_file($child, $importer) || self::is_folder($child, $importer)) {
                        $children[] = (string)$link['href'];
                    }
                    else {
                        $importer->trace("NOTICE: Child $child->id of folder $entry->id won't be imported by the file plugin because it is not a file or folder");
                    }
                }
                else {
                    $importer->trace("WARNING: folder $entry->id claims to have child $link[href] which does not exist");
                }
            }
        }

        if ($recurse) {
            foreach ($children as $childid) {
                $child = $importer->get_entry_by_id($childid);
                if (self::is_folder($child, $importer)) {
                    $children = array_merge($children, self::get_children_of_folder($child, $importer, true));
                }
            }
        }

        return $children;
    }

    /**
     * Creates a file artefact based on the given entry.
     *
     * @param SimpleXMLElement $entry The entry to base the file's data on
     * @param PluginImport $importer  The importer
     * @param int $parent             The ID of the parent artefact for this file
     * @throws ImportException If the given entry is not detected as being a file
     * @return ArtefactTypeFile The file artefact created
     */
    public static function create_file(SimpleXMLElement $entry, PluginImport $importer, $parent=null) {
        if (!self::is_file($entry, $importer)) {
            throw new ImportException($importer, "create_file(): Cannot create a file artefact from an entry we don't recognise as a file");
        }

        // TODO: make sure there's no arbitrary file inclusion
        // TODO: the src attribute must be an IRI, according to the ATOM spec. 
        // This means that it could have UTF8 characters in it, and the PHP 
        // documentation doesn't sound hopeful that urldecode will work with 
        // UTF8 characters
        $pathname = urldecode((string)$entry->content['src']);
        // TODO: might want to make it easier to get at the directory where the import files are
        $data = $importer->get('data');
        $dir = dirname($data['filename']);

        // Note: this data is passed (eventually) to ArtefactType->__construct, 
        // which calls strtotime on the dates for us
        $data = (object)array(
            'title' => (string)$entry->title,
            'owner' => $importer->get('usr'),
            'filetype' => (string)$entry->content['type'],
        );
        if (isset($entry->summary)) {
            $data->description = (string)$entry->summary;
        }
        if ($published = strtotime((string)$entry->published)) {
            $data->ctime = (string)$entry->published;
        }
        if ($updated = strtotime((string)$entry->updated)) {
            $data->mtime = (string)$entry->updated;
        }

        if ($parent) {
            $data->parent = $parent;
        }

        $pathname = $dir . DIRECTORY_SEPARATOR . $pathname;

        // This API sucks, but that's not my problem
        if (!$id = ArtefactTypeFile::save_file($pathname, $data, $importer->get('usrobj'))) {
            throw new ImportException($importer, 'TODO: get_string: was unable to import file');
        }

        // Work out if the file was really a profile icon
        $isprofileicon = false;
        $match = $entry->xpath('mahara:artefactplugin[@mahara:plugin="file" and @mahara:type="profileicon"]');
        if (count($match) == 1) {
            $isprofileicon = true;
        }

        $artefact = artefact_instance_from_id($id);
        // Work around that save_file doesn't let us set the mtime
        $artefact->set('mtime', strtotime((string)$entry->updated));
        if ($isprofileicon) {
            $artefact->set('artefacttype', 'profileicon');
            $artefact->set('parent', null);

            // Sadly the process for creating a profile icon is a bit dumb. To 
            // be honest, it shouldn't even be a separate artefact type
            $basedir = get_config('dataroot') . 'artefact/file/';
            $olddir  = 'originals/' . ($id % 256) . '/';
            $newdir  = 'profileicons/originals/' . ($id % 256) . '/';
            check_dir_exists($basedir . $newdir);
            if (!rename($basedir  . $olddir . $id, $basedir . $newdir . $id)) {
                throw new ImportException($importer, 'TODO: get_string: was unable to move profile icon');
            }

            // Unconditionally set as default, even if there is more than one
            $importer->get('usrobj')->profileicon = $id;
            $importer->get('usrobj')->commit();
        }

        $artefact->commit();

        return $artefact;
    }

    /**
     * Creates a folder artefact based on the given entry.
     *
     * @param SimpleXMLElement $entry The entry to base the folder's data on
     * @param PluginImport $importer  The importer
     * @param int $parent             The ID of the parent artefact for this folder
     * @throws ImportException If the given entry is not detected as being a folder
     * @return int The ID of the folder artefact created
     */
    private static function create_folder(SimpleXMLElement $entry, PluginImport $importer, $parent=null) {
        if (!self::is_folder($entry, $importer)) {
            throw new ImportException($importer, "create_folder(): Cannot create a folder artefact from an entry we don't recognise as a folder");
        }

        $folder = new ArtefactTypeFolder();
        $folder->set('title', (string)$entry->title);
        $folder->set('description', PluginImportLeap::get_entry_content($entry, $importer));
        if ($published = strtotime((string)$entry->published)) {
            $folder->set('ctime', $published);
        }
        if ($updated = strtotime((string)$entry->updated)) {
            $folder->set('mtime', $updated);
        }
        $folder->set('owner', $importer->get('usr'));
        $folder->set('tags', PluginImportLeap::get_entry_tags($entry));
        if ($parent) {
            $folder->set('parent', $parent);
        }
        $folder->commit();
        return $folder->get('id');
    }

    /**
     * Creates a folder, and recursively, all folders and files under it.
     *
     * @param SimpleXMLElement $entry The entry to base the folder's data on
     * @param PluginImport $importer  The importer
     * @param int $parent             The ID of the parent artefact for this folder
     * @throws ImportException If the given entry is not detected as being a folder
     * @return array The artefact mapping for the folder and all children - a 
     *               list of entry ID => artefact IDs for each entry processed. See
     *               PluginImport::import_from_load_mapping() for more information
     */
    private static function create_folder_and_children(SimpleXMLElement $entry, PluginImport $importer, $parent=null) {
        if (!self::is_folder($entry, $importer)) {
            throw new ImportException($importer, "create_folder(): Cannot create a folder artefact from an entry we don't recognise as a folder");
        }

        $artefactmapping = array();

        // Create the folder
        $folderid = self::create_folder($entry, $importer, $parent);
        $artefactmapping[(string)$entry->id] = array($folderid);

        // Then create all folders/files under it
        foreach (self::get_children_of_folder($entry, $importer) as $childid) {
            $child = $importer->get_entry_by_id($childid);
            if (self::is_folder($child, $importer)) {
                $result = self::create_folder_and_children($child, $importer, $folderid);
                $artefactmapping = array_merge($artefactmapping, $result);
            }
            else {
                $artefactmapping[$childid] = array(self::create_file($child, $importer, $folderid)->get('id'));
            }
        }

        return $artefactmapping;
    }

}

?>
