import { onMounted, watch } from 'vue'

const flavor = ref('standalone')

import { ref } from 'vue'

export function useFlavor() {
  watch(() => flavor.value, (flavor) => {
    const root = document.documentElement
    root.style.setProperty('--db-tools-standalone', flavor==='standalone' ? 'unset' : 'none')
    root.style.setProperty('--db-tools-symfony', flavor==='symfony' ? 'unset' : 'none')

    localStorage.setItem("flavor", flavor)
  })

  onMounted(() => {
    const storedFlavor = localStorage.getItem("flavor")
    if (storedFlavor) {
      flavor.value = storedFlavor
    }
  })

  return {
    flavor
  }
}