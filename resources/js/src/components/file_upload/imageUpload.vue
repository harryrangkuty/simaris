<script lang="ts">
import { defineComponent } from 'vue';
import { PlusOutlined, LoadingOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';

export default defineComponent({
  props: {
    modelValue: String,
    path: String,
  },
  emits: ['update:modelValue'],
  components: {
    PlusOutlined,
    LoadingOutlined,
  },
  data() {
    return {
      loading: false,
      imageUrl: this.modelValue || '',
    };
  },
  watch: {
    modelValue(newValue) {
      this.imageUrl = newValue;
    },
  },
  methods: {
    getBase64(img: Blob, callback: (base64Url: string) => void) {
      const reader = new FileReader();
      reader.addEventListener('load', () => callback(reader.result as string));
      reader.readAsDataURL(img);
    },
    handleChange(info: any) {
      const file = info.file.originFileObj;
      if (file) {
        this.loading = true;
        this.getBase64(file, (base64Url) => {
          this.imageUrl = base64Url;
          this.loading = false;
          this.$emit('update:modelValue', base64Url);
        });
      } else {
        message.error('File tidak valid atau gagal diproses.');
      }
    },
    beforeUpload(file: File) {
      const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png';
      if (!isJpgOrPng) {
        message.error('You can only upload JPG or PNG files!');
        return false;
      }
      const isLt2M = file.size / 1024 / 1024 < 2;
      if (!isLt2M) {
        message.error('Image must be smaller than 2MB!');
        return false;
      }
      return true;
    },
    getImageUrl(imageUrl) {
      if (imageUrl && imageUrl.startsWith('data:image') || imageUrl.includes('simsdm')) {
        return imageUrl;
      } else{
        return `${this.host}/storage/${this.path}/${imageUrl}`;
      }
    }
  },
});
</script>

<template>
  <div class="flex flex-row items-center gap-x-4">
    <div v-if="imageUrl" class="w-1/2 overflow-hidden rounded-lg">
      <img :src="getImageUrl(imageUrl)" alt="Preview" class="w-full h-full object-cover object-top" />
    </div>
    <a-upload
      name="avatar"
      list-type="picture-card"
      class="w-1/2"
      :show-upload-list="false"
      :before-upload="beforeUpload"
      @change="handleChange"
      :custom-request="() => {}"
    >
      <div>
        <loading-outlined v-if="loading" />
        <plus-outlined v-else />
        <div class="mt-2 text-gray-600">Upload</div>
      </div>
    </a-upload>
  </div>
</template>