    <tr title="{$artefact->hovertitle|escape}">
        <td style="width: 20px;">
            {if $selectone}<input type="radio" class="radio"id="{$elementname}_{$artefact->id}" name="{$elementname}" value="{$artefact->id|escape}"{if $value == $artefact->id} checked="checked"{/if}>
            {else}<input type="checkbox" id="{$elementname}_{$artefact->id}" name="{$elementname}[{$artefact->id}]"{if $value && in_array($artefact->id, $value)} checked="checked"{/if}>
            <input type="hidden" name="{$elementname}_onpage[]" value="{$artefact->id}">
            {/if}</td>
        <td style="width: 22px;"><label for="{$elementname}_{$artefact->id}"><img src="{$artefact->icon|escape}" alt="*"></label></td>
        <th><label for="{$elementname}_{$artefact->id}">{if $artefact->description}{$artefact->description|escape}{else}{$artefact->title|escape}{/if}{if $artefact->artefacttype == 'profileicon'} ({str tag=profileicon section=artefact.internal}){/if}</label></th>
    </tr>