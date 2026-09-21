import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

const repositoryUrl = 'https://github.com/mortalkiller/filament-complete-user-profile';
const basePath = '/filament-complete-user-profile';

export default defineConfig({
  site: 'https://docs.pedromonteiro.dev',
  base: basePath,
  trailingSlash: 'always',
  integrations: [
    starlight({
      title: 'Filament Complete User Profile',
      description: 'A modular account center for Filament 5 and Laravel 13.',
      favicon: `${basePath}/favicon-32x32.png`,
      head: [
        { tag: 'link', attrs: { rel: 'icon', href: `${basePath}/favicon.ico`, sizes: 'any' } },
        { tag: 'link', attrs: { rel: 'icon', type: 'image/png', sizes: '96x96', href: `${basePath}/favicon-96x96.png` } },
        { tag: 'link', attrs: { rel: 'icon', type: 'image/png', sizes: '48x48', href: `${basePath}/favicon-48x48.png` } },
        { tag: 'link', attrs: { rel: 'icon', type: 'image/png', sizes: '16x16', href: `${basePath}/favicon-16x16.png` } },
        { tag: 'link', attrs: { rel: 'apple-touch-icon', sizes: '180x180', href: `${basePath}/apple-touch-icon.png` } },
        { tag: 'link', attrs: { rel: 'manifest', href: `${basePath}/site.webmanifest` } },
        { tag: 'meta', attrs: { name: 'theme-color', content: '#000000' } },
        { tag: 'meta', attrs: { name: 'msapplication-TileColor', content: '#000000' } },
        { tag: 'meta', attrs: { name: 'msapplication-config', content: `${basePath}/browserconfig.xml` } },
      ],
      logo: {
        src: './src/assets/PM-02.png',
        alt: 'Pedro Monteiro',
      },
      social: [
        { icon: 'github', label: 'GitHub', href: repositoryUrl },
        { icon: 'external', label: 'Pedro Monteiro', href: 'https://pedromonteiro.dev' },
      ],
      editLink: {
        baseUrl: `${repositoryUrl}/edit/main/docs-site/src/content/docs/`,
      },
      customCss: ['./src/styles/custom.css'],
      sidebar: [
        {
          label: 'Getting Started',
          items: [
            'getting-started/installation',
            'getting-started/compatibility',
            'getting-started/configuration',
          ],
        },
        {
          label: 'Guides',
          items: [
            'guides/profile',
            'guides/navigation',
            'guides/mfa',
            'guides/sessions',
            'guides/api-tokens',
            'guides/account-security',
            'guides/tenant-scoped-tokens',
            'guides/custom-profile-fields',
            'guides/custom-account-sections',
            'guides/diagnostics',
            'guides/storage',
          ],
        },
        {
          label: 'API Reference',
          items: [
            'api',
            'api/complete-user-profile-plugin',
            'api/account-section',
            'api/profile',
            'api/security',
            'api/sessions',
            'api/api-tokens',
            'api/contracts',
            'api/concerns',
            'api/configuration',
            'api/extension-points',
          ],
        },
        {
          label: 'Development',
          items: [
            'development/local-development',
            'development/testing',
          ],
        },
        {
          label: 'Project',
          items: [
            'project/roadmap',
            'project/security',
          ],
        },
      ],
    }),
  ],
});
