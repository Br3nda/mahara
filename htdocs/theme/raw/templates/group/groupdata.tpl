<div class="preview-group">
  <h3>{$group->name|escape}</h3>
  {if $group->description}<p id="group-description">{$group->description|escape}</p> {/if}
  {include file="group/info.tpl"}
</div>
