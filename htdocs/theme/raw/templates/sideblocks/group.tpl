    <h3>{$data.group->name|escape}</h3>
        <div class="sidebar-content">
    <ul>
    {foreach from=$data.menu item=item}
        {if $item.path != 'groups'}
        <li><a href="{$WWWROOT}{$item.url}">{$item.title}</a>
            {if $item.path == 'groups/forums' && !empty($data.forums)}
            <ul>
                {foreach from=$data.forums item=forum}
                <li><a href="{$WWWROOT}interaction/forum/view.php?id={$forum->id}">{$forum->title|escape}</a></li>
                {/foreach}
            </ul>
            {/if}
        </li>
        {/if}
    {/foreach}
    </ul>
</div>
