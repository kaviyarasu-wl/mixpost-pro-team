<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { debounce } from 'lodash'
import { getWindowDimensions } from '@/helpers'

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  columns: {
    type: Number,
    default: 3
  }
})

const bottomRef = ref(null)
const columnsList = ref([])
const ready = ref(false)

onMounted(() => {
  redraw()
  window.addEventListener('resize', resizeHandler)
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeHandler)
})

const resizeHandler = debounce(() => {
  if (columnsList.value.length !== newColumns().length) {
    redraw()
  }
}, 300)

const newColumns = () => {
  let count = props.columns

  if (getWindowDimensions().width <= 768) {
    count = 2
  }

  const cols = []

  for (let i = 0; i < count; i++) {
    cols.push({ i, indexes: [] })
  }

  return cols
}

const addItem = index => {
  const columnIndex = index % columnsList.value.length

  columnsList.value[columnIndex].indexes.push(index)
}

const fill = () => {
  for (let i = 0; i < props.items.length; i++) {
    addItem(i)
  }
}

const redraw = () => {
  ready.value = false
  columnsList.value.splice(0)
  columnsList.value.push(...newColumns())
  ready.value = true
  fill()
}

watch(
  () => props.items,
  () => {
    redraw()
  }
)
</script>
<template>
  <div class="flex -m-1" :class="{ 'opacity-0': !ready }">
    <div
      v-for="(column, index) in columnsList"
      :key="index"
      class="flex flex-col grow basis-0 px-1"
    >
      <div v-for="i in column.indexes" :key="i" :ref="`item_${i}`" class="py-1">
        <slot :item="items[i]" :index="i">{{ items[i] }}</slot>
      </div>

      <div ref="bottomRef" class="grow -mt-20 pt-20 min-h-[100px] -z-10" :data-column="index" />
    </div>
  </div>
</template>
