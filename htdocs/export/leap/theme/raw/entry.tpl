{include file="export:leap:entryheader.tpl"}
        <title>{$title|escape}</title>
        <id>{$id}</id>
{if $author}        <author>
            <name>{$author|display_name|escape}</name>
        </author>
{/if}
{if $updated}        <updated>{$updated}</updated>
{/if}
{if $created}        <published>{$created}</published>
{/if}
{if $summary}        <summary>{$summary}</summary>
{/if}
        <content{if $contenttype != 'text'} type="{$contenttype}"{/if}{if $contentsrc} src="{$contentsrc}"{/if}>{if $contenttype == 'xhtml'}<div xmlns="http://www.w3.org/1999/xhtml">{/if}{if $contenttype == 'xhtml'}{$content|clean_html|export_leap_rewrite_links}{elseif $contenttype == 'html'}{$content|clean_html|export_leap_rewrite_links|escape}{else}{$content|escape}{/if}{if $contenttype == 'xhtml'}</div>{/if}</content>
        <rdf:type rdf:resource="leaptype:{$leaptype}"/>
{if $artefacttype}        <mahara:artefactplugin mahara:type="{$artefacttype}" mahara:plugin="{$artefactplugin}"/>
{/if}
{include file="export:leap:links.tpl"}
{include file="export:leap:categories.tpl"}
{if !$skipfooter}
{include file="export:leap:entryfooter.tpl"}
{/if}
