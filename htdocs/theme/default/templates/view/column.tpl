<div id="column_{$column}" class="column columns{$numcolumns}">
    <div class="column-header">
    {if $column == 1}
        <div class="add-column-left">
            <input type="submit" class="submit addcolumn" name="action_addcolumn_before_1" value="Add Column">
        </div>
    {/if}
        <div class="remove-column">
            <input type="submit" class="submit removecolumn" name="action_removecolumn_column_{$column}" value="Remove Column">
        </div>
    {if $column == $numcolumns}
        <div class="add-column-right">
            <input type="submit" class="submit addcolumn" name="action_addcolumn_before_{$column + 1}" value="Add Column">
        </div>
    {else}
        <div class="add-column-center">
            <input type="submit" class="submit addcolumn" name="action_addcolumn_before_{$column + 1}" value="Add Column">
        </div>
    {/if}
    </div>
    <div class="column-content">
    {if !$javascript}
        <div class="add-button">
            <input type="submit" class="submit newblockhere" name="action_addblocktype_column_{$column}_order_1" value="Add new block here">
        </div>
    {/if} 
    {$blockcontent} 
    </div>
</div>

