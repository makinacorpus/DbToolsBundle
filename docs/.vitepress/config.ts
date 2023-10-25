import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'en',
  title: "DbToolsBundle",
  srcDir: "content",
  cleanUrls: true,
  base: "/",
  metaChunk: false,
  head: [
    ['link', { rel: 'icon', href: '/images/logo.svg' }]
  ],
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/images//logo.svg',
    nav: [
      { text: 'Home', link: '/' },
    ],
    editLink: {
      pattern: 'https://gitlab.makina-corpus.net/chalets/OMAE/-/tree/main/docs/content/:path',
      text: 'Edit this page on Github'
    },
    docFooter: {
      prev: 'Previous page',
      next: 'Next page'
    },
    outlineTitle: 'On this page',
    lastUpdated: {
      text: 'Last updated',
      formatOptions: {
        dateStyle: 'short'
      }
    },
    search: {
      provider: 'local'
    },
    sidebar: [
      {
        text: 'Reference',
        collapsed: false,
        items: [
          { text: 'Introduction', link: '/introduction' },
          {
            text: 'Getting Started',
            collapsed: false,
            items: [
              { text: 'Installation', link: '/getting-started/installation' },
              { text: 'Basics', link: '/getting-started/basics' },
            ]
          },
          { text: 'Bundle configuration', link: '/configuration' },
        ]
      },
      {
        text: 'Anonymization',
        collapsed: false,
        items: [
          { text: 'Essentials', link: '/anonymization/essentials' },
          { text: 'Core Anonymizers', link: '/anonymization/core-anonymizers' },
          { text: 'Custom Anonymizers', link: '/anonymization/custom-anonymizers' },
          { text: 'Pack FR_Fr', link: '/anonymization/pack-FR-Fr' },
        ]
      },
      {
        text: 'Statistics',
        link: '/stats',
      }
    ]
  }
})
