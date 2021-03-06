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
 * @subpackage artefact-blog
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

/** 
 * Users can create blogs and blog posts using this plugin.
 */
class PluginArtefactBlog extends PluginArtefact {

    public static function get_artefact_types() {
        return array(
            'blog',
            'blogpost',
        );
    }
    
    public static function get_block_types() {
        return array();
    }

    public static function get_plugin_name() {
        return 'blog';
    }

    public static function menu_items() {
        return array(
            array(
                'path'   => 'myportfolio/blogs',
                'url'    => 'artefact/blog/',
                'title'  => get_string('myblogs', 'artefact.blog'),
                'weight' => 30,
            ),
        );
    }

    public static function get_cron() {
        return array();
    }


    public static function block_advanced_options_element($configdata, $artefacttype) {
        $strartefacttype = get_string($artefacttype, 'artefact.blog');
        return array(
            'type' => 'fieldset',
            'name' => 'advanced',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('moreoptions', 'artefact.blog'),
            'elements' => array(
                'copytype' => array(
                    'type' => 'select',
                    'title' => get_string('blockcopypermission', 'view'),
                    'description' => get_string('blockcopypermissiondesc', 'view'),
                    'defaultvalue' => isset($configdata['copytype']) ? $configdata['copytype'] : 'nocopy',
                    'options' => array(
                        'nocopy' => get_string('copynocopy', 'artefact.blog'),
                        'reference' => get_string('copyreference', 'artefact.blog', $strartefacttype),
                        'full' => get_string('copyfull', 'artefact.blog', $strartefacttype),
                    ),
                ),
            ),
        );
    }

}

/**
 * A Blog artefact is a collection of BlogPost artefacts.
 */
class ArtefactTypeBlog extends ArtefactType {

    /**
     * This constant gives the per-page pagination for listing blogs.
     */
    const pagination = 10;


    /**
     * We override the constructor to fetch the extra data.
     *
     * @param integer
     * @param object
     */
    public function __construct($id = 0, $data = null) {
        parent::__construct($id, $data);

        if (empty($this->id)) {
            $this->container = 1;
        }
    }

    public function is_container() {
        return true;
    }

    /**
     * This function updates or inserts the artefact.  This involves putting
     * some data in the artefact table (handled by parent::commit()), and then
     * some data in the artefact_blog_blog table.
     */
    public function commit() {
        // Just forget the whole thing when we're clean.
        if (empty($this->dirty)) {
            return;
        }
      
        // We need to keep track of newness before and after.
        $new = empty($this->id);
        
        // Commit to the artefact table.
        parent::commit();

        $this->dirty = false;
    }

    /**
     * This function extends ArtefactType::delete() by deleting blog-specific
     * data.
     */
    public function delete() {
        if (empty($this->id)) {
            return;
        }

        // Delete the artefact and all children.
        parent::delete();
    }

    /**
     * Checks that the person viewing this blog is the owner. If not, throws an 
     * AccessDeniedException. Used in the blog section to ensure only the 
     * owners of the blogs can view or change them there. Other people see 
     * blogs when they are placed in views.
     */
    public function check_permission() {
        global $USER;
        if ($USER->get('id') != $this->owner) {
            throw new AccessDeniedException(get_string('youarenottheownerofthisblog', 'artefact.blog'));
        }
    }


    public function describe_size() {
        return $this->count_children() . ' ' . get_string('posts', 'artefact.blog');
    }

    /**
     * Renders a blog for a view. This involves using a tablerenderer to paginate the posts.
     *
     * This uses some legacy stuff from the old views interface, including its 
     * dependence on javascript and the table renderer, which would be nice to 
     * fix using the new pagination stuff some time.
     *
     * @param  array  Options for rendering
     * @return array  A two key array, 'html' and 'javascript'.
     */
    public function render_self($options) {
        $this->add_to_render_path($options);

        $smarty = smarty_core();
        if (isset($options['viewid'])) {
            $smarty->assign('artefacttitle', '<a href="' . get_config('wwwroot') . 'view/artefact.php?artefact='
                                             . $this->get('id') . '&view=' . $options['viewid']
                                             . '">' . hsc($this->get('title')) . '</a>');
        }
        else {
            $smarty->assign('artefacttitle', hsc($this->get('title')));
        }

        $smarty->assign('options', $options);
        $smarty->assign('description', clean_html($this->get('description')));

        // Remove unnecessary options for blog posts
        unset($options['hidetitle']);

        $page = (isset($options['page'])) ? abs(intval($options['page'])) : abs(param_integer('page', 1));
        $offset = $page ? $page * self::pagination - self::pagination : 1;

        $postids = get_column_sql("
            SELECT a.id
            FROM {artefact} a
            LEFT JOIN {artefact_blog_blogpost} bp ON a.id = bp.blogpost
            WHERE a.parent = ?
            AND bp.published = 1
            ORDER BY a.ctime DESC
            LIMIT ? OFFSET ?", array($this->get('id'), self::pagination, $offset));
        $postcount = $this->count_published_posts();

        $data = array();
        foreach($postids as $postid) {
            $blogpost = new ArtefactTypeBlogPost($postid);
            $data[] = array(
                'id' => $postid,
                'content' => $blogpost->render_self($options)
            );
        }
        $smarty->assign('postdata', $data);

        // Pagination
        if ($postcount > self::pagination) {
            $baselink = get_config('wwwroot') . 'view/artefact.php?artefact=' . $this->get('id');
            if (isset($options['viewid'])) {
                $baselink .= '&view=' . $options['viewid'];
            }

            if ($offset + self::pagination < $postcount) {
                $smarty->assign('olderpostslink',  $baselink . '&page=' . ($page + 1));
            }
            if ($offset > 0) {
                $smarty->assign('newerpostslink',  $baselink . '&page=' . ($page - 1));
            }
        }

        return array('html' => $smarty->fetch('blocktype:blog:blog_render_self.tpl'), 'javascript' => '');
    }

                
    public static function get_icon($options=null) {
    }

    public static function is_singular() {
        return false;
    }

    public static function collapse_config() {
    }

    /**
     * This function returns a list of the given user's blogs.
     *
     * @param User
     * @return array (count: integer, data: array)
     */
    public static function get_blog_list(User $user, $limit = self::pagination, $offset = 0) {
        ($result = get_records_sql_array("
         SELECT id, title, description
         FROM {artefact}
         WHERE owner = ?
          AND artefacttype = 'blog'
         ORDER BY title
         LIMIT ? OFFSET ?", array($user->get('id'), $limit, $offset)))
            || ($result = array());

        $count = (int)get_field('artefact', 'COUNT(*)', 'owner', $user->get('id'), 'artefacttype', 'blog');

        return array($count, $result);
    }

    /**
     * This function creates a new blog.
     *
     * @param User
     * @param array
     */
    public static function new_blog(User $user, array $values) {
        $artefact = new ArtefactTypeBlog();
        $artefact->set('title', $values['title']);
        $artefact->set('description', $values['description']);
        $artefact->set('owner', $user->get('id'));
        $artefact->set('tags', $values['tags']);
        $artefact->commit();
    }

    /**
     * This function updates an existing blog.
     *
     * @param User
     * @param array
     */
    public static function edit_blog(User $user, array $values) {
        if (empty($values['id']) || !is_numeric($values['id'])) {
            return;
        }

        $artefact = new ArtefactTypeBlog($values['id']);
        if ($user->get('id') != $artefact->get('owner')) {
            return;
        }
        
        $artefact->set('title', $values['title']);
        $artefact->set('description', $values['description']);
        $artefact->set('tags', $values['tags']);
        $artefact->commit();
    }

    public static function get_links($id) {
        $wwwroot = get_config('wwwroot');

        return array(
            '_default'                                  => $wwwroot . 'artefact/blog/view/?id=' . $id,
            get_string('blogsettings', 'artefact.blog') => $wwwroot . 'artefact/blog/settings/?id=' . $id,
        );
    }

    public function copy_extra($new) {
        $new->set('title', get_string('Copyof', 'mahara', $this->get('title')));
    }

    /**
     * Returns the number of posts in this blog that have been published.
     *
     * The result of this function looked up from the database each time, so 
     * cache it if you know it's safe to do so.
     *
     * @return int
     */
    public function count_published_posts() {
        return (int)get_field_sql("
            SELECT COUNT(*)
            FROM {artefact} a
            LEFT JOIN {artefact_blog_blogpost} bp ON a.id = bp.blogpost
            WHERE a.parent = ?
            AND bp.published = 1", array($this->get('id')));
    }

}

/**
 * BlogPost artefacts occur within Blog artefacts
 */
class ArtefactTypeBlogPost extends ArtefactType {

    /**
     * This defines whether the blogpost is published or not.
     *
     * @var boolean
     */
    protected $published = false;

    /**
     * We override the constructor to fetch the extra data.
     *
     * @param integer
     * @param object
     */
    public function __construct($id = 0, $data = null) {
        parent::__construct($id, $data);

        if ($this->id) {
            if ($bpdata = get_record('artefact_blog_blogpost', 'blogpost', $this->id)) {
                foreach($bpdata as $name => $value) {
                    if (property_exists($this, $name)) {
                        $this->$name = $value;
                    }
                }
            }
            else {
                // This should never happen unless the user is playing around with blog post IDs in the location bar or similar
                throw new ArtefactNotFoundException(get_string('blogpostdoesnotexist', 'artefact.blog'));
            }
        }
    }

    /**
     * This method extends ArtefactType::commit() by adding additional data
     * into the artefact_blog_blogpost table.
     *
     * This method also works out what blockinstances this blogpost is in, and 
     * informs them that they should re-check what artefacts they have in them.
     * The post content may now link to different artefacts. See {@link 
     * PluginBlocktypeBlogPost::get_artefacts for more information}
     */
    public function commit() {
        if (empty($this->dirty)) {
            return;
        }

        db_begin();
        $new = empty($this->id);
      
        parent::commit();

        $this->dirty = true;

        $data = (object)array(
            'blogpost'  => $this->get('id'),
            'published' => ($this->get('published') ? 1 : 0)
        );

        if ($new) {
            insert_record('artefact_blog_blogpost', $data);
        }
        else {
            update_record('artefact_blog_blogpost', $data, 'blogpost');
        }

        // We want to get all blockinstances that contain this blog post. That is currently:
        // 1) All blogpost blocktypes with this post in it
        // 2) All blog blocktypes with this posts's blog in it
        //
        // With these, we tell them to rebuild what artefacts they have in them, 
        // since the post content could have changed and now have links to 
        // different artefacts in it
        $blockinstanceids = (array)get_column_sql('SELECT block
            FROM {view_artefact}
            WHERE artefact = ?
            OR artefact = ?', array($this->get('id'), $this->get('parent')));
        if ($blockinstanceids) {
            require_once(get_config('docroot') . 'blocktype/lib.php');
            foreach ($blockinstanceids as $id) {
                $instance = new BlockInstance($id);
                $instance->rebuild_artefact_list();
            }
        }

        db_commit();
        $this->dirty = false;
    }

    /**
     * This function extends ArtefactType::delete() by also deleting anything
     * that's in blogpost.
     */
    public function delete() {
        if (empty($this->id)) {
            return;
        }

        $this->detach(); // Detach all file attachments
        delete_records('artefact_blog_blogpost', 'blogpost', $this->id);
      
        parent::delete();
    }

    /**
     * Checks that the person viewing this blog is the owner. If not, throws an 
     * AccessDeniedException. Used in the blog section to ensure only the 
     * owners of the blogs can view or change them there. Other people see 
     * blogs when they are placed in views.
     */
    public function check_permission() {
        global $USER;
        if ($USER->get('id') != $this->owner) {
            throw new AccessDeniedException(get_string('youarenottheownerofthisblogpost', 'artefact.blog'));
        }
    }
  
    public function describe_size() {
        return $this->count_attachments() . ' ' . get_string('attachments', 'artefact.blog');
    }

    public function render_self($options) {
        $smarty = smarty_core();
        if (empty($options['hidetitle'])) {
            if (isset($options['viewid'])) {
                $smarty->assign('artefacttitle', '<a href="' . get_config('wwwroot') . 'view/artefact.php?artefact='
                     . $this->get('id') . '&amp;view=' . $options['viewid']
                     . '">' . hsc($this->get('title')) . '</a>');
            }
            else {
                $smarty->assign('artefacttitle', hsc($this->get('title')));
            }
        }

        // We need to make sure that the images in the post have the right viewid associated with them
        $postcontent = clean_html($this->get('description'));
        if (isset($options['viewid'])) {
            safe_require('artefact', 'file');
            $postcontent = ArtefactTypeFolder::append_view_url($postcontent, $options['viewid']);
        }
        $smarty->assign('artefactdescription', $postcontent);
        $smarty->assign('artefact', $this);
        $attachments = $this->get_attachments();
        if ($attachments) {
            $this->add_to_render_path($options);
            require_once(get_config('docroot') . 'artefact/lib.php');
            foreach ($attachments as &$attachment) {
                $f = artefact_instance_from_id($attachment->id);
                $attachment->size = $f->describe_size();
                $attachment->iconpath = $f->get_icon(array('id' => $attachment->id, 'viewid' => isset($options['viewid']) ? $options['viewid'] : 0));
                $attachment->viewpath = get_config('wwwroot') . 'view/artefact.php?artefact=' . $attachment->id . '&view=' . (isset($options['viewid']) ? $options['viewid'] : 0);
                $attachment->downloadpath = get_config('wwwroot') . 'artefact/file/download.php?file=' . $attachment->id;
                if (isset($options['viewid'])) {
                    $attachment->downloadpath .= '&view=' . $options['viewid'];
                }
            }
            $smarty->assign('attachments', $attachments);
        }
        $smarty->assign('postedbyon', get_string('postedbyon', 'artefact.blog',
                                                 display_name($this->owner),
                                                 format_date($this->ctime)));
        return array('html' => $smarty->fetch('artefact:blog:render/blogpost_renderfull.tpl'),
                     'javascript' => '');
    }


    public function can_have_attachments() {
        return true;
    }


    public static function get_icon($options=null) {
    }

    public static function is_singular() {
        return false;
    }

    public static function collapse_config() {
    }

    /**
     * This function returns a list of the current user's blog posts, for the
     * given blog.
     *
     * @param User
     * @param integer
     * @param integer
     */
    public static function get_posts(User $user, $id, $limit = self::pagination, $offset = 0) {
        ($result = get_records_sql_assoc("
         SELECT a.id, a.title, a.description, " . db_format_tsfield('a.ctime', 'ctime') . ', ' . db_format_tsfield('a.mtime', 'mtime') . ", bp.published
         FROM {artefact} a
          LEFT OUTER JOIN {artefact_blog_blogpost} bp
           ON a.id = bp.blogpost
         WHERE a.parent = ?
          AND a.artefacttype = 'blogpost'
          AND a.owner = ?
         ORDER BY bp.published ASC, a.ctime DESC
         LIMIT ? OFFSET ?;", array(
            $id,
            $user->get('id'),
            $limit,
            $offset
        )))
            || ($result = array());

        $count = (int)get_field('artefact', 'COUNT(*)', 'owner', $user->get('id'), 
                                'artefacttype', 'blogpost', 'parent', $id);

        if (count($result) > 0) {
            // Get the attached files.
            $files = ArtefactType::attachments_from_id_list(array_map(create_function('$a', 'return $a->id;'), $result));
            if ($files) {
                safe_require('artefact', 'file');
                foreach ($files as &$file) {
                    $file->icon = call_static_method(generate_artefact_class_name($file->artefacttype), 'get_icon', array('id' => $file->attachment));
                    $result[$file->artefact]->files[] = $file;
                }
            }

            // Format dates properly
            foreach ($result as &$post) {
                $post->ctime = format_date($post->ctime, 'strftimedaydatetime');
                $post->mtime = format_date($post->mtime);
                $post->description = clean_html($post->description);
            }
        }

        return array($count, array_values($result));
    }

    /** 
    /**
     * This function creates a new blog post.
     *
     * @param User
     * @param array
     */
    public static function new_post(User $user, array $values) {
        $artefact = new ArtefactTypeBlogPost();
        $artefact->set('title', $values['title']);
        $artefact->set('description', $values['description']);
        $artefact->set('published', $values['published']);
        $artefact->set('owner', $user->get('id'));
        $artefact->set('parent', $values['parent']);
        $artefact->commit();
        return true;
    }

    /** 
     * This function updates an existing blog post.
     *
     * @param User
     * @param array
     */
    public static function edit_post(User $user, array $values) {
        $artefact = new ArtefactTypeBlogPost($values['id']);
        if ($user->get('id') != $artefact->get('owner')) {
            return false;
        }

        $artefact->set('title', $values['title']);
        $artefact->set('description', $values['description']);
        $artefact->set('published', $values['published']);
        $artefact->set('tags', $values['tags']);
        $artefact->commit();
        return true;
    }


    /**
     * This function publishes the blog post.
     *
     * @return boolean
     */
    public function publish() {
        if (!$this->id) {
            return false;
        }
        
        $data = (object)array(
            'blogpost'  => $this->id,
            'published' => 1
        );

        if (get_field('artefact_blog_blogpost', 'COUNT(*)', 'blogpost', $this->id)) {
            update_record('artefact_blog_blogpost', $data, 'blogpost');
        }
        else {
            insert_record('artefact_blog_blogpost', $data);
        }
        return true;
    }

    
    public static function get_links($id) {
        $wwwroot = get_config('wwwroot');

        return array(
            '_default'                                  => $wwwroot . 'artefact/blog/post.php?blogpost=' . $id,
        );
    }

    public function update_artefact_references(&$view, &$template, &$artefactcopies, $oldid) {
        parent::update_artefact_references($view, $template, $artefactcopies, $oldid);
        // Attach copies of the files that were attached to the old post.
        // Update <img> tags in the post body to refer to the new image artefacts.
        $regexp = array();
        $replacetext = array();
        if (isset($artefactcopies[$oldid]->oldattachments)) {
            foreach ($artefactcopies[$oldid]->oldattachments as $a) {
                if (isset($artefactcopies[$a])) {
                    $this->attach($artefactcopies[$a]->newid);
                }
                $regexp[] = '#<img([^>]+)src="' . get_config('wwwroot') . 'artefact/file/download.php\?file=' . $a . '"#';
                $replacetext[] = '<img$1src="' . get_config('wwwroot') . 'artefact/file/download.php?file=' . $artefactcopies[$a]->newid . '"';
            }
            $this->set('description', preg_replace($regexp, $replacetext, $this->get('description')));
        }
    }

    /**
     * During the copying of a view, we might be allowed to copy
     * blogposts but not the containing blog.  We need to create a new
     * blog to hold the copied posts.
     */
    public function default_parent_for_copy(&$view, &$template, $artefactstoignore) {
        static $blogid;

        if (!empty($blogid)) {
            return $blogid;
        }

        $blogname = get_string('viewposts', 'artefact.blog', $view->get('id'));
        $data = (object) array(
            'title'       => $blogname,
            'description' => get_string('postscopiedfromview', 'artefact.blog', $template->get('title')),
            'owner'       => $view->get('owner'),
            'group'       => $view->get('group'),
            'institution' => $view->get('institution'),
        );
        $blog = new ArtefactTypeBlog(0, $data);
        $blog->commit();

        $blogid = $blog->get('id');

        return $blogid;
    }

    /**
     * Looks through the blog post text for links to download artefacts, and 
     * returns the IDs of those artefacts.
     */
    public function get_referenced_artefacts_from_postbody() {
        return artefact_get_references_in_html($this->get('description'));
    }
}


?>
