<div class="friends">
{if $friends}
    <table id="userfriendstable">
    {foreach from=$friends item=row}
        <tr>
        {foreach from=$row item=friend}
            <td class="r{cycle values=0,1} friendcell">
                <a href="{$WWWROOT}user/view.php?id={$friend}">
                   <img src="{$WWWROOT}thumb.php?type=profileicon&amp;maxwidth=60&amp;maxheight=60&amp;id={$friend}" alt="">
                   <br>{$friend|display_default_name|escape}
                </a>
            </td>
        {/foreach}
        </tr>
    {/foreach}
    </table>
{else}
    {if $USERID == $USER->get('id')}
        <div class="message">Try <a href="{$WWWROOT}user/find.php">searching for friends</a>!</div>
    {else}
        {if $relationship == 'none' && $friendscontrol == 'auto'}
            <div class="message">{$newfriendform}</div>
        {elseif $relationship == 'none' && $friendscontrol == 'auth'}
            <div class="message"><a href="{$WWWROOT}user/requestfriendship.php?id={$USERID}&amp;returnto=view" class="btn-request">{str tag='requestfriendship' section='group'}</a></div>
        {elseif $relationship == 'requestedfriendship'}
            <div class="message">{str tag=friendshiprequested section=group}</div>
        {/if}
        {* Case not covered here: friendscontrol disallows new users. The block will appear empty. *}
    {/if}
{/if}
</div>
