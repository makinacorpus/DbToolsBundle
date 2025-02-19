import { onMounted, watch } from 'vue'
import { ref } from 'vue'

export const flavorList = [
  'standalone',
  'symfony',
  'laravel',
  'docker',
]
const getCombos = (a: string[]) => {
  const separator = '-';
  const o = Object();
  for (let i = 0; i < a.length; ++i) {
    for (let j = i + 1; j <= a.length; ++j) {
      const left = a.slice(i, j);
      const right = a.slice(j, a.length);
      o[left.join(separator)] = 1;
      for (let k = 0; k < right.length; ++k) {
        o[[...left, right[k]].join(separator)] = 1;
      }
    }
  }
  return Object.keys(o);
}
export const flavorCombinationList = getCombos(flavorList)

const flavor = ref('standalone')

export function useFlavor() {
  watch(() => flavor.value, (flavor) => {
    const root = document.documentElement
    flavorCombinationList.forEach(f => {
      root.style.setProperty('--db-tools-' + f, f.includes(flavor) ? 'unset' : 'none')
    })

    localStorage.setItem("flavor", flavor)
  }, { immediate: true })

  onMounted(() => {
    const storedFlavor = localStorage.getItem("flavor")
    if (storedFlavor) {
      flavor.value = storedFlavor
    }

    // initialize style
    const style = document.createElement('style')
    style.innerHTML = ''
    flavorCombinationList.forEach(f => {
      style.innerHTML += `
        .main [db-tools-flavor~='${f}'] {
          display: var(--db-tools-${f});
        }
      `
    })
    document.head.appendChild(style)
  })

  return {
    flavor
  }
}
