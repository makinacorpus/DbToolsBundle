// .vitepress/theme/index.js
import DefaultTheme from 'vitepress/theme'
import './custom.css'
import MakinaCorpusHorizontal from './MakinaCorpusHorizontal.vue'
import MakinaCorpusSquare from './MakinaCorpusSquare.vue'
import { h } from 'vue'
import DbToolsBundleActions from './DbToolsBundleActions.vue'
import DbToolsBundleHomeImage from './DbToolsBundleHomeImage.vue'

export default {
  extends: DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {
      'aside-bottom': () => h(MakinaCorpusSquare),
      'home-features-after': () => h(MakinaCorpusHorizontal),
      'home-hero-actions-after': () => h(DbToolsBundleActions),
      'home-hero-image': () => h(DbToolsBundleHomeImage)
    })
  },
  VPHomeHero: DbToolsBundleActions,
}