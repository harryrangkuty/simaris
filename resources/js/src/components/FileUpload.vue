<script>
import { defineComponent } from 'vue';
import { PlusOutlined } from '@ant-design/icons-vue';
import { Upload } from 'ant-design-vue';

export default defineComponent({
  components: {
    PlusOutlined,
  },
  props: {
    value: {
      type: [Array, Object],
      required: false,
      default: () => []
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    accept: {
      type: String,
      default: '',
    },
  },
  computed: {
    fileList: {
      get() {
        if (this.multiple) {
          return Array.isArray(this.value) ? this.value : [];
        }
        return this.value ? [this.value] : [];
      },
      set(value) {
        if (this.multiple) {
          this.$emit('update:value', value);
        } else {
          this.$emit('update:value', value.length > 0 ? value[0] : null);
        }
      },
    },
  },
  data() {
    return {
      previewVisible: false,
      previewImage: '',
      previewTitle: '',
    };
  },
  methods: {
    handleCancel() {
      this.previewVisible = false;
      this.previewTitle = '';
    },
    async handlePreview(file) {
      if (!file.url && !file.preview) {
        // jika ada origin file nya (saat add)
        if(file.originFileObj){
          file.preview = await this.getBase64(file.originFileObj);
        }
        // jika tidak ada origin file nya (saat update)
        else{
          file.preview = file.thumbUrl;
        }
      }
      this.previewImage = file.url || file.preview;
      this.previewVisible = true;
      this.previewTitle = file.name || file.url.substring(file.url.lastIndexOf('/') + 1);
    },
    getBase64(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
      });
    },
    beforeUpload(file) {
      if (this.accept) {
        const acceptedTypes = this.accept.split(',').map(ext => ext.trim().toLowerCase());
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();

        if (!acceptedTypes.includes(fileExt)) {
          this.$message.error(`File ${file.name} tidak sesuai format yang diizinkan. Format yang diizinkan: ${acceptedTypes.join(', ')}`);
          return Upload.LIST_IGNORE;
        }
      }

      if (this.multiple) {
        this.fileList = [...this.fileList, file];
      } else {
        this.fileList = [file];
      }
      return false;
    },
  },
});
</script>

<template>
  <div class="clearfix">
    <a-upload
      v-model:file-list="fileList"
      list-type="picture-card"
      :before-upload="beforeUpload"
      :accept="accept"
      @preview="handlePreview"
    >
      <!-- <div v-if="fileList.length < 8"> Ujicoba batas 8 file-->
      <div>
        <plus-outlined />
        <div>Upload</div>
      </div>
    </a-upload>
    <a-modal :open="previewVisible" :title="previewTitle" :footer="null" @cancel="handleCancel">
      <img alt="example" style="width: 100%" :src="previewImage" />
    </a-modal>
  </div>
</template>

<style scoped>
.ant-upload-select-picture-card i {
  font-size: 32px;
  color: #999;
}

.ant-upload-select-picture-card .ant-upload-text {
  margin-top: 8px;
  color: #666;
}
</style>