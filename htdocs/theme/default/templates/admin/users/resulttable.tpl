            {if !empty($results.data)}
                <h3>{str tag="results"}</h3>
                <table id="searchresults" class="tablerenderer">
                    <thead>
                      <tr>
                        <th></th>
                        <th>{str tag="username"}</th>
                        <th>{str tag="name"}</th>
                        <th>{str tag=email}</th>
                        <th>{str tag=institution}</th>
                      </tr>
                    </thead>
                    <tbody>
                {foreach from=$results.data item=r}
                      <tr>
                        <td><img src="{$WWWROOT}thumb.php?type=profileicon&size=40x40&id={$r.id}" alt="{str tag=profileimage}" /></td>
                        <td><a href="{$WWWROOT}user/view.php?id={$r.id}">{$r.username}</a></td>
                        <td>{$r.firstname} {$r.lastname}</td>
                        <td>{$r.email}</td>
                        <td>{$r.institution}</td>
                      </tr>
                {/foreach}
                    </tbody>
                {if count($results.data) < $results.count}
                    <tfoot class="search-results-pages">
                      <tr>
                        <td colspan=5>
                          {if $results.page > $results.prev}
                            <span class="search-results-page prev"><a href="?{$params}&amp;offset={$results.limit*$results.prev}">{str tag=prevpage}</a></span>
                          {/if}
                          {foreach from=$pagenumbers item=i}
                            <span class="search-results-page{if $i == $results.page} selected{/if}"><a href="?{$params}&amp;offset={$i*$results.limit}">{$i+1}</a></span>
                          {/foreach}
                          {if $results.page < $results.next}
                            <span class="search-results-page next"><a href="?{$params}&amp;offset={$results.limit*$results.next}">{str tag=nextpage}</a></span>
                          {/if}
                        </td>
                      </tr>
                    </tfoot>
                {/if}
                </table>
            {else}
                <div>{str tag="noresultsfound"}</div>
            {/if}