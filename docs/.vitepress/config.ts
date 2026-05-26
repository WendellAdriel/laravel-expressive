import { defineConfig } from 'vitepress'

const configuredBase = process.env.VITEPRESS_BASE ?? '/'
const base = configuredBase.endsWith('/') ? configuredBase : `${configuredBase}/`

export default defineConfig({
  title: 'Expressive',
  description: 'Typed Objects for Eloquent',
  base,
  head: [
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Expressive' }],
    ['meta', { property: 'og:description', content: 'Typed Objects for Eloquent' }],
    ['meta', { property: 'og:image', content: 'https://laravel-expressive.wendelladriel.com/banner.png' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: 'Expressive' }],
    ['meta', { name: 'twitter:description', content: 'Typed Objects for Eloquent' }],
    ['meta', { name: 'twitter:image', content: 'https://laravel-expressive.wendelladriel.com/banner.png' }],
  ],
  themeConfig: {
    sidebar: [
      { text: 'Overview', link: '/' },
      { text: 'Installation', link: '/installation' },
      { text: 'Configuration', link: '/configuration' },
      { text: 'Defining Expressive Classes', link: '/defining-expressives' },
      { text: 'Converting Models', link: '/converting-models' },
      { text: 'Relationships', link: '/relationships' },
      { text: 'Serialization', link: '/serialization' },
      { text: 'Persistence', link: '/persistence' },
      { text: 'Generating Expressive Classes', link: '/generator' },
      { text: 'Syncing Generated Classes', link: '/syncing' },
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
