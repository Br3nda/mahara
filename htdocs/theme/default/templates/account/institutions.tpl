{include file="header.tpl"}
{include file="sidebar.tpl"}

{include file="columnleftstart.tpl"}
<h2>{str tag="institutionmembership"}</h2>
{$memberform}
{$requestedform}
{$invitedform}
{if !empty($joinform)}
  <h6>{str tag="requestmembershipofaninstitution"}</h6>
  {$joinform}
{/if}
{include file="columnleftend.tpl"}
{include file="footer.tpl"}