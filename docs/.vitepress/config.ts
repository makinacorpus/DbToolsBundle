import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'en',
  title: "DbToolsBundle",
  srcDir: "content",
  base: "/",
  metaChunk: false,
  head: [
    ['link', { rel: 'icon', href: '/logo.svg' }]
  ],
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.svg',
    nav: [
      { text: 'Home', link: '/' },
    ],
    editLink: {
      pattern: 'https://github.com/makinacorpus/DbToolsBundle/blob/main/docs/content/:path',
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
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2023-present <a href="https://makina-corpus.com">Makina Corpus</a>'
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/makinacorpus/DbToolsBundle' }
    ],
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
          { text: 'Database vendors support', link: '/database-vendors' },
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
          { text: 'Internals', link: '/anonymization/internals' },
        ]
      },
      {
        text: 'Statistics',
        link: '/stats',
      }
    ]
  }
})
