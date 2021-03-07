<template>
  <div>
    <label for>Upload Ad Images</label>
    <el-upload
      ref="upload"
      action="/api/upload-a-image"
      :http-request="handleUpload"
      name="images"
      list-type="picture-card"
      :limit="limit"
      :on-remove="handleRemove"
      :on-preview="handlePictureCardPreview"
      :before-upload="beforeAvatarUpload"
      :on-change="handelFileChange"
      multiple
    >
      <i slot="default" class="el-icon-plus" />
    </el-upload>
    <!-- <el-upload
      ref="upload"
      action="/api/upload-a-image"
      :auto-upload="false"
      list-type="picture-card"
      multiple
      name="images"
      :limit="limit"
      :before-upload="beforeAvatarUpload"
      :on-preview="handlePictureCardPreview"
      :on-exceed="showFileExceedMessage"
      :on-remove="handleRemove"
      :on-change="handelFileChange"
      :headers="myHeaders"
      :file-list="fileList"
    >
      <i slot="trigger" class="el-icon-plus" />
      <el-button style="margin-left: 10px;" size="small" type="warning" @click="submitUpload">upload to server</el-button>
    </el-upload> -->
    <el-dialog :visible.sync="dialogVisible">
      <img width="100%" :src="dialogImageUrl" alt>
    </el-dialog>
    <!-- <small style="color: #c08943">
      Maximum Dimension: <strong>{{ maxwidth }}px by {{ maxheight }}px</strong><br>
      Minimum Dimension: <strong>{{ minwidth }}px by {{ minheight }}px</strong><br>
      Each Image must not exceed 3 Mb and
      <br>Supported formats are *.jpg, *.gif and *.png
    </small> -->
  </div>
</template>
<script>
import Resource from '@/api/resource';
const uploadAdImage = new Resource('upload-a-image');
const removeAdImage = new Resource('delete-media');
export default {
  props: {
    disabled: {
      type: Boolean,
      default: () => true,
    },
    fileList: {
      type: Array,
      default: () => [],
    },
    siteId: {
      type: Number,
      default: () => null,
    },
    itemId: {
      type: Number,
      default: () => null,
    },
  },
  data() {
    return {
      maxwidth: 800,
      maxheight: 525,
      minwidth: 700,
      minheight: 459,
      dimension_met: false,
      dialogImageUrl: '',
      dialogVisible: false,
      // fileList: [],
      limit: 3,
      myHeaders: {
        Authorization: '',
      },
    };
  },
  created() {
    this.myHeaders.Authorization = 'Bearer ' + this.$store.getters.token;
  },
  methods: {
    submitUpload() {
      console.log(this.$refs.upload);
      this.$refs.upload.submit();
    },
    handelFileChange(file, fileList) {
      this.$emit('input', fileList);
      // this.handleUpload();
    },
    handleUpload: function(file) {
      const app = this;
      const formData = new FormData();
      formData.append('uid', file.file.uid);
      formData.append('images', file.file);
      formData.append('site_id', app.siteId);
      formData.append('item_id', app.itemId);
      uploadAdImage.store(formData).then(function(res) {
      });
      // }
      return false; // Return false does not upload automatically
    },
    handleRemove(file, fileList) {
      // this.$emit('input', fileList);
      var uid = file.uid;
      const param = { uid: uid };
      removeAdImage
        .store(param)
        .then(function(res) {})
        .catch((error) => {
          console.log(error);
        });
      this.$emit('input', fileList);

      // return new Promise((resolve, reject) => {
      //   this.$confirm('Are you sure you want to delete this photo?')
      //     .then(_ => {
      //       this.fileList = fileList;
      //       console.log(fileList);
      //       resolve();
      //     })
      //     .catch(_ => {
      //       reject();
      //     });
      // });
    },
    handlePictureCardPreview(file) {
      this.dialogImageUrl = file.url;
      this.dialogVisible = true;
    },
    handleAvatarSuccess(res, file) {
      console.log(file);
      this.dialogImageUrl = URL.createObjectURL(file);
    },
    beforeAvatarUpload(file) {
      const app = this;
      var img = new Image();
      // var imgwidth = 0;
      // var imgheight = 0;
      // var maxwidth = app.maxwidth;
      // var maxheight = app.maxheight;
      // var minwidth = app.minwidth;
      // var minheight = app.minheight;
      var dimension_met;

      img.src = URL.createObjectURL(file);
      img.onload = function() {
        // imgwidth = this.width;
        // imgheight = this.height;
        dimension_met = true;
        // if ((imgwidth <= maxwidth && imgheight <= maxheight) && (imgwidth >= minwidth && imgheight <= minheight)) {
        //   dimension_met = true;
        // } else {
        //   dimension_met = false;
        // }
        if (!dimension_met) {
          app.$message.error('Make sure your image is meets our range of dimensions');
          return false;
        }
        const isJPGOrPng =
        file.type === 'image/jpeg' ||
        file.type === 'image/png' ||
        file.type === 'image/gif';
        const isLt2M = file.size / 1024 / 1024 < 3;
        if (!isJPGOrPng) {
          this.$message.error('Image must be in JPG, PNG or GIF format!');
          return false;
        }
        if (!isLt2M) {
          this.$message.error('Image size can not exceed 3MB!');
          return false;
        }
        return dimension_met;
      };
      // return img.onload();
    },
    // validateImage(file, fileList) {
    //   if (!this.beforeAvatarUpload(file)) {
    //     fileList.splice(fileList.indexOf(file), 1);
    //   }
    //   // this.fileList = fileList;
    //   this.$emit('input', fileList);
    // },
    showFileExceedMessage(files, fileList) {
      this.$message.error(
        'Maximum of ' + this.limit + ' images are allowed in bulk'
      );
    },
  },
};
</script>
