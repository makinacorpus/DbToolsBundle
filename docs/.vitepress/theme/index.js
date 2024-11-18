// .vitepress/theme/index.js
import DefaultTheme from 'vitepress/theme'
import { h } from 'vue'
import './custom.css'
import MakinaCorpusHorizontal from './components/MakinaCorpusHorizontal.vue'
import MakinaCorpusSquare from './components/MakinaCorpusSquare.vue'
import DbToolsBundleActions from './components/DbToolsBundleActions.vue'
import DbToolsBundleHomeImage from './components/DbToolsBundleHomeImage.vue'
import DbToolsBundleFlavorSwitcherWrapper from './components/DbToolsBundleFlavorSwitcherWrapper.vue'
import DbToolsBundleFlavorSwitcher from './components/DbToolsBundleFlavorSwitcher.vue'

export default {
  extends: DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {
      'aside-bottom': () => h(MakinaCorpusSquare),
      'home-features-after': () => h(MakinaCorpusHorizontal),
      'home-hero-actions-after': () => h(DbToolsBundleActions),
      'home-hero-image': () => h(DbToolsBundleHomeImage),
      'sidebar-nav-before': () => h(DbToolsBundleFlavorSwitcherWrapper)
    })
  },
  enhanceApp({ app }) {
    app.component('FlavorSwitcher', DbToolsBundleFlavorSwitcher)
  }
}