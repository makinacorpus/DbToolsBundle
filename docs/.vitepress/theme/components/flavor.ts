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
  watch(() => flavor.value, () => {
    onFlavorUpdate()
  })

  onMounted(() => {
    const storedFlavor = localStorage.getItem("db-tools-flavor")
    if (storedFlavor) {
      flavor.value = storedFlavor
    }
    onFlavorUpdate()

    // initialize style
    // we display standalone flavor at start
    const style = document.createElement('style')
    style.innerHTML = ''
    flavorCombinationList.forEach(f => {
      style.innerHTML += `
        :root {
          --db-tools-span-${f}: ${f.includes('standalone') ? 'inline' : 'none'};
          --db-tools-div-${f}: ${f.includes('standalone') ? 'block' : 'none'};
        }
        .main span[db-tools-flavor~='${f}'] {
          display: var(--db-tools-span-${f});
        }
        .main div[db-tools-flavor~='${f}'] {
          display: var(--db-tools-div-${f});
        }
      `
    })
    document.head.appendChild(style)
  })

  const onFlavorUpdate = () => {
    const root = document.documentElement
    flavorCombinationList.forEach(f => {
      root.style.setProperty('--db-tools-span-' + f, f.includes(flavor.value) ? 'inline' : 'none')
      root.style.setProperty('--db-tools-div-' + f, f.includes(flavor.value) ? 'block' : 'none')
    })
    root.style.setProperty('--db-tools-flavor-color', 'var(--db-tools-flavor-' + flavor.value + ')')
    root.style.setProperty('--db-tools-flavor-logo-color', 'var(--db-tools-flavor-logo-' + flavor.value + ')')

    localStorage.setItem("db-tools-flavor", flavor.value)
  }

  return {
    flavor
  }
}
