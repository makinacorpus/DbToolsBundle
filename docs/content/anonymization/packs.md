<script setup>
import VPSocialLink from 'vitepress/dist/client/theme-default/components/VPSocialLink.vue'
</script>

# Extra packs

With *DbToolsBundle*, we chose to have a decentralized approach, we don't want the base
code to become too big. The bundle comes with a short list of common purpose anonymizers and
we won't add too specific ones to it.

Instead we added the possibility to create and include **extra packs of anonymizers**.

If you can't find what you need with anonymizers provided by the core, look at these packs.

:::tip
And if you can't find it with them either, then it's may be time to
[create your own ones](./custom-anonymizers), and may be to
[share them with the community](../contribute/pack)!
:::

Here is a list of official packs:

::: details Pack fr-FR

A pack of anonymizers for fr-FR locale.

```sh
composer require dbtoolsbundle/pack-fr-fr
```

* `fr-fr.address`: Same as address from core but with a sample of 500 dumb french addresses
* `fr-fr.firstname`: Anonymize with a random french first names from a sample of ~500 items
* `fr-fr.lastname`: Anonymize with a random french last names from a sample of ~500 items
* `fr-fr.phone`: Anonymize french telephone numbers, (only option is `mode`: `mobile` or `landline`


<VPSocialLink icon="github" link="https://github.com/DbToolsBundle/pack-fr-fr"/>
:::

:::info
All official packs are weekly tested, see all pack status on the [DbToolsBundle/packs-status repository](https://github.com/DbToolsBundle/packs-status)
:::

:::tip
These packs can be provided by *DbToolsbundle* team or by the community.
[Look for more of them on github](https://github.com/topics/db-tools-bundle-pack).
:::
