<script>
export default {
    emits: ["update:selectedKeys", "update:openKeys"],
    props: {
        menu: {
            type: Array,
            default: () => []
        },
        selectedKeys: {
            type: Array,
            default: () => []
        },
        openKeys: {
            type: Array,
            default: () => []
        },
        collapsed: {
            type: Boolean,
            default: false
        },
        accordion: {
            type: Function,
            default: null
        }
    },
    data() {
        return {
            ready: false
        }
    },
    mounted() {
        this.initializeMenu()
    },
    methods: {
        onSelect(keys) {
            this.$emit("update:selectedKeys", keys);
        },
        onOpenChange(keys) {
            this.$emit("update:openKeys", keys);
        },
        initializeMenu() {
            const keys = this.selectedKeys

            if (keys && keys.length) {
                const activeKey = keys[0]

                const parent = this.menu.find(m =>
                    m.submenu?.some(sm => sm.key === activeKey)
                )

                if (parent && !this.openKeys.includes(parent.key)) {
                    const newOpenKeys = [...this.openKeys, parent.key]
                    this.$emit("update:openKeys", newOpenKeys)
                }
            }

            this.$nextTick(() => {
                this.ready = true
                this.scrollToActive()
            })
        },
        scrollToActive() {
            this.$nextTick(() => {
                const active = this.$el.querySelector(".ant-menu-item-selected")

                if (active) {
                    active.scrollIntoView({
                        block: "center",
                        behavior: "auto"
                    })
                }
            })
        }
    }
};
</script>

<template>
    <a-menu v-if="ready" ref="menuRef" :selectedKeys="selectedKeys" :openKeys="openKeys" mode="inline"
        @select="onSelect" @openChange="onOpenChange" class="bg-purple-100">
        <template v-for="m in menu" :key="m.key ?? m.title">
            <template v-if="!m.key">
                <hr class="my-2 border-purple-300/70" />
                <span v-if="!collapsed" class="px-4 text-sm font-semibold module-grouping-title">
                    {{ m.title }}
                </span>
            </template>

            <template v-else-if="m.submenu">
                <a-sub-menu :key="m.key" :title="m.title">
                    <template v-if="m.icon" #icon>
                        <Icon :icon="m.icon" class="icon-animate !text-2xl" />
                    </template>
                    <a-menu-item v-for="sm in m.submenu" :key="sm.key">
                        <template v-if="sm.icon" #icon>
                            <Icon :icon="sm.icon" class="!text-lg" />
                        </template>
                        <a :href="publicPath(sm.url)">{{ sm.title }}</a>
                    </a-menu-item>
                </a-sub-menu>
            </template>

            <template v-else>
                <a-menu-item :key="m.key">
                    <template v-if="m.icon" #icon>
                        <Icon :icon="m.icon" class="icon-animate !text-2xl" />
                    </template>
                    <a :href="publicPath(m.url)">{{ m.title }}</a>
                </a-menu-item>
            </template>
        </template>
    </a-menu>
</template>