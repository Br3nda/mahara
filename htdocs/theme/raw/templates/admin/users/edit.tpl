{include file="header.tpl"}

{include file="columnfullstart.tpl"}
<div id="edituser">
    <div class="fr"><a href="{$WWWROOT}user/view.php?id={$user->id}"><img src="{$WWWROOT}thumb.php?type=profileiconbyid&amp;maxwidth=100&amp;maxheight=100&amp;id={$user->profileicon}" alt=""></a></div>
    <h2><a href="{$WWWROOT}user/view.php?id={$user->id}">{$user|display_name|escape}</a></h2>
    {if !empty($loginas)}
      <span class="addicon fr"><a href="{$WWWROOT}admin/users/changeuser.php?id={$user->id}">{$loginas}</a></span>
    {/if}

    {if $suspended}
    <div class="message">
      <h4>{$suspendedby|escape}</h4>
      {if $user->suspendedreason}
      <div id="suspendreason">
        <h5>{str tag="suspendedreason" section="admin"}:</h5>
        {$user->suspendedreason|format_whitespace}
      </div>
      {/if}
      <div class="center">{$suspendform2}</div>
    </div>
    {/if}

    <h3>{str tag="siteaccountsettings" section="admin"}</h3>
    <p>{str tag="usereditdescription" section="admin"}</p>
    {$siteform}
    <!--<h3>{str tag="suspenduser" section="admin"}</h3>-->
    {if $USER->get('admin') || (!$user->get('admin') && !$user->get('staff')) }
    <hr>
    <h3 id="suspend">{str tag="suspenddeleteuser" section=admin}</h3>
    <p>{str tag="suspenddeleteuserdescription" section=admin}</p>
		{if $USER->get('admin')}
		<div id="delete">
			<h4>{str tag=deleteuser section=admin}</h4>
			<p>{str tag=deleteusernote section=admin}</p>
			{$deleteform}
		</div>
		{/if}
    	<div id="suspenddelete">
        	<h4>{str tag="suspenduser" section=admin}</h4>
                {$suspendform}
        </div>
    {/if}
	<div class="clearer"></div>

    {if ($institutions)}
    <hr>
    <h3 id="institutions">{str tag="institutionsettings" section="admin"}</h3>
    <p>{str tag="institutionsettingsdescription" section="admin"}</p>
    {$institutionform}
    {/if}
</div>
{include file="columnfullend.tpl"}
{include file="footer.tpl"}

