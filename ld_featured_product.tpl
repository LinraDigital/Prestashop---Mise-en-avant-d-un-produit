<section class="ld-featured-product">
    <div class="container">
        <div class="content">
            <div class="row">
                <div class="col-lg-6">
                    {if $ld_featured.eyebrow}<small>{$ld_featured.eyebrow|escape:'html':'UTF-8'}</small>{/if}
                    <h2 class="title">{$ld_featured.name|escape:'html':'UTF-8'}</h2>

                    {* {if $ld_featured.description_short}
                        <div class="desc">{$ld_featured.description_short nofilter}</div>
                    {/if} *}

                    <div class="price">
                        <span class="price-current">{$ld_featured.price}</span>
                        {if $ld_featured.price_before}
                            <span class="price-before">{$ld_featured.price_before}</span>
                        {/if}

                        <span class="color-gray ms-2 ml-2" style="font-size: 0.7rem">{$ld_featured.tax_label|escape:'html':'UTF-8'}</span>
                    </div>

                    <a href="{$ld_featured.link|escape:'html':'UTF-8'}" class="btn btn-primary">
                        <span class="btn-arrow">❯</span> {$ld_featured.btn_text|escape:'html':'UTF-8'}
                    </a>
                </div>

                <div class="col-lg-6">
                    <div class="picture">
                        <img src="{$ld_featured.cover}" alt="{$ld_featured.name|escape:'html':'UTF-8'}" id="FeaturedImg">
                    </div>

                    {if $ld_featured.gallery|count > 1}
                    <div class="pictures">
                        {foreach from=$ld_featured.gallery item=img}
                            <button type="button" class="pict" data-large="{$img.large}">
                                <img src="{$img.thumb}" alt="">
                            </button>
                        {/foreach}
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</section>
