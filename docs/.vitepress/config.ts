import { defineConfig } from 'vitepress'

const configuredBase = process.env.VITEPRESS_BASE ?? '/'
const base = configuredBase.endsWith('/') ? configuredBase : `${configuredBase}/`

export default defineConfig({
  title: 'Expressive',
  description: 'Typed Objects for Eloquent',
  base,
  head: [
    ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' }],
    ['link', { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Cascadia+Code:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Expressive' }],
    ['meta', { property: 'og:description', content: 'Typed Objects for Eloquent' }],
    ['meta', { property: 'og:image', content: 'https://laravel-expressive.wendelladriel.com/banner.png' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: 'Expressive' }],
    ['meta', { name: 'twitter:description', content: 'Typed Objects for Eloquent' }],
    ['meta', { name: 'twitter:image', content: 'https://laravel-expressive.wendelladriel.com/banner.png' }],
  ],
  markdown: {
    theme: {
      light: 'catppuccin-latte',
      dark: 'catppuccin-mocha',
    },
  },
  themeConfig: {
    sidebar: [
      { text: 'Overview', link: '/' },
      {
        text: 'Getting Started',
        items: [
          { text: 'Installation', link: '/getting-started/installation' },
          { text: 'Configuration', link: '/getting-started/configuration' },
          { text: 'Changelog', link: '/getting-started/changelog' },
          { text: 'Resources', link: '/getting-started/resources' },
        ],
      },
      {
        text: 'The Basics',
        items: [
          { text: 'Defining Expressive Classes', link: '/basics/defining-expressives' },
          { text: 'Converting Models', link: '/basics/converting-models' },
          { text: 'Relationships', link: '/basics/relationships' },
          { text: 'Serialization', link: '/basics/serialization' },
          { text: 'Persistence', link: '/basics/persistence' },
        ],
      },
      {
        text: 'Commands',
        items: [
          { text: 'Generating Expressive Classes', link: '/commands/generator' },
          { text: 'Syncing Generated Classes', link: '/commands/syncing' },
        ],
      },
    ],
    search: {
      provider: 'local',
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/wendelladriel/laravel-expressive' },
    ],
    editLink: {
      pattern: 'https://github.com/wendelladriel/laravel-expressive/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
  },
})
