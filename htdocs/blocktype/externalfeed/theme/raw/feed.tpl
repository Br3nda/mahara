<div id="blocktype_externalfeed_feed">
    {if $feedimage}<div class="fr">{$feedimage}</div>{/if}
    <div id="blocktype_externalfeed_title">
    <a href="{$url|escape}"><img src="{theme_path location="images/rss.png"}"></a>
    {if $link}<a href="{$link|escape}">{/if}
    {$title|escape}
    {if $link}</a>{/if}
    </div>
    {if $description != $entries[0]->description}<div id="blocktype_externalfeed_desc">{$description|clean_html}</a></div>{/if}
    <div id="blocktype_externalfeed_entries">
        {if $full}
            {foreach from=$entries item=entry}
                <h4>
                {if $entry->link}<a href="{$entry->link|escape}">{/if}
                {$entry->title|escape}
                {if $entry->link}</a>{/if}
                </h4>
                {$entry->description|clean_html}
            {/foreach}
        {else}
            <ol>
            {foreach from=$entries item=entry}
                <li>
                {if $entry->link}<a href="{$entry->link|escape}">{/if}
                {$entry->title|escape}
                {if $entry->link}</a>{/if}
                </li>
            {/foreach}
            </ol>
        {/if}
    </div>
    <div id="blocktype_externalfeed_lastupdate">
    {$lastupdated}
    </div>
</div>
