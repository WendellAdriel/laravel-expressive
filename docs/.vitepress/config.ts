import { defineConfig } from 'vitepress'

const configuredBase = process.env.VITEPRESS_BASE ?? '/'
const base = configuredBase.endsWith('/') ? configuredBase : `${configuredBase}/`

export default defineConfig({
  title: 'Expressive',
  description: 'A Data Mapper layer on top of Eloquent',
  base,
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
