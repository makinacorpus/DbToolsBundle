import { onMounted, watch } from 'vue'

const flavour = ref('standalone')

import { ref } from 'vue'

export function useFlavour() {
  watch(() => flavour.value, (flavour) => {
    const root = document.documentElement
    root.style.setProperty('--db-tools-standalone', flavour==='standalone' ? 'unset' : 'none')
    root.style.setProperty('--db-tools-symfony', flavour==='symfony' ? 'unset' : 'none')

    localStorage.setItem("flavour", flavour)
  })

  onMounted(() => {
    const storedFlavour = localStorage.getItem("flavour")
    if (storedFlavour) {
      flavour.value = storedFlavour
    }
  })

  return {
    flavour
  }
}