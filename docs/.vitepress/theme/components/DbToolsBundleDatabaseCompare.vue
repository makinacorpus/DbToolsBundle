<template>
  <figure class="image-compare" @mousemove.prevent="onMouseMove" @touchstart="onMouseMove($event, true)" @touchmove="onMouseMove($event, true)" @click="onMouseMove($event, true)">
    <div class="image-compare-wrapper" :style="{ width: posX + 'px' }">
      <img :src="withBase('/db-plain.png')" :style="dimensions">
    </div>
    <img :src="withBase('/db-anonymized.png')" :style="dimensions">
    <div class="image-compare-handle" :style="{ left: posX + 'px' }" @mousedown.prevent="onMouseDown">
      <span class="image-compare-handle-icon left">
        <img class="light" :src="withBase('/database.svg')"/>
        <img class="dark" :src="withBase('/database-d.svg')"/>
        <small>Original</small>
      </span>
      <span class="image-compare-handle-icon right">
        <img class="light" :src="withBase('/anonymize.svg')"/>
        <img class=" dark" :src="withBase('/anonymize-d.svg')"/>
        <small>Anonymized</small>
      </span>
    </div>
  </figure>
</template>

<script>
import { withBase } from 'vitepress'

export default {
  data() {
    return {
      width: null,
      height: null,
      pageX: null,
      posX: null,
      isDragging: false,
      allowNextFrame: true,
      unwatch: null
    }
  },
  computed: {
    dimensions() {
      return {
        width: `${this.width}px`,
        height: 'auto'
      }
    }
  },
  methods: {
    onResize() {
      this.width = this.$el.clientWidth;
      this.height = this.$el.clientHeight;
    },
    onMouseDown() {
			this.isDragging = true;
    },
    onMouseUp(event) {
      event.preventDefault();

      this.isDragging = false;
    },
    onMouseMove(event, isDragging = this.isDragging) {
      if (isDragging && this.allowNextFrame) {
        this.allowNextFrame = false;
        this.pageX = event.pageX || event.targetTouches[0].pageX || event.originalEvent.targetTouches[0].pageX;

        window.requestAnimationFrame(this.updatePos);
      }
		},
    updatePos() {
      let posX = this.pageX - this.$el.getBoundingClientRect().left;

      this.posX = posX;
      this.allowNextFrame = true;
    },
    setInitialPosX() {
      this.posX = this.width * 3 / 7
    },
    withBase
  },
  created() {
    window.addEventListener('mouseup', this.onMouseUp);
    window.addEventListener('resize', this.onResize);
  },
  mounted() {
    this.onResize();
    this.setInitialPosX()
  },
  beforeDestroy() {
    this.unwatch();
    window.removeEventListener('mouseup', this.onMouseUp);
    window.removeEventListener('resize', this.onResize);
  }
};
</script>

<style>
.image-compare {
  position: relative;
  margin: 0;
  margin-top: 16px;
  border-radius: 12px;
  overflow: hidden;

  img {
    max-width: none;
    display: block;
  }
}

.image-compare-wrapper,
.image-compare-handle {
  bottom: 0;
  position: absolute;
  top: 0;
}

.image-compare-wrapper {
  left: 0;
  overflow: hidden;
  width: 100%;
  z-index: 1;
  transform: translateZ(0);
  will-change: width;
}

.image-compare-handle {
  color: var(--vp-c-brand);
  background-color: currentColor;
  cursor: ew-resize;
  transform: translateX(-50%) translateZ(0);
  width: 4px;
  z-index: 2;
  will-change: left;
}

.image-compare-handle-icon {
  position: absolute;
  bottom: -25px;
  left: 50%;
  font-size: 2rem;
  color: currentColor;
  line-height: normal;
  display: flex;
  flex-direction: column;
  align-items: center;

  img {
    background-color: color-mix(in srgb, var(--vp-c-bg-soft) 80%, transparent);
    padding: 3px 3px 0 3px;
  }
  small {
    background-color: color-mix(in srgb, var(--vp-c-bg-soft) 80%, transparent);
    padding: 2px 4px;
    font-size: 14px;
  }
  &.left {
    align-items: end;
    transform: translate(calc(-100% - 2px), -50%);
    img {
      border-radius: 8px 0 0 0;
    }
    small {
      border-radius: 8px 0 0 0;
    }
  }

  &.right {
    align-items: start;
    transform: translate(2px, -50%);
    img {
      border-radius: 0 8px 0 0;
    }
    small {
      border-radius: 0 8px 0 0;
    }
  }

  img {
    height: 2rem;
    width: 2rem;
  }
}

html.dark .image-compare-handle .light {
  display: none;
}

html:not(.dark) .image-compare-handle .dark {
  display: none;
}
</style>
