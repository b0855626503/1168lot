<template>
    <div>
        <div class="image-wrapper" v-if="removed">
            <image-item
                v-for='(image, index) in items'
                :key='image.id'
                v-bind:image="image"
                :imgpath="imgpath"
                :input-name="inputName"
                :required="required"
                :multiple="multiple"
                :remove-button-label="removeButtonLabel"
                @onRemoveImage="removeImage($event)"
                @changeupload="changeUpload($event)"
            ></image-item>
        </div>

        <div class="image-wrapper" v-else>
            <image-item
                v-for='(image, index) in items'
                :key='image.id'
                v-bind:image="image"
                :imgpath="imgpath"
                :input-name="inputName"
                :required="required"
                :multiple="multiple"
                @changeupload="changeUpload($event)"
                @onRemoveImage="removeImage($event)"
            ></image-item>
        </div>

        <label class="btn btn-sm btn-info" style="display: none; width: 90%;text-align: center;margin: 0 auto;" @click="createFileType" id="btnadd_img">{{ buttonLabel }}</label>
    </div>
</template>

<script>
    export default {
        props: {
            buttonLabel: {
                type: String,
                required: false,
                default: 'Add Image'
            },

            removeButtonLabel: {
                type: String,
                required: false,
                default: 'Remove Image'
            },

            inputName: {
                type: String,
                required: false,
                default: 'attachments'
            },
            imgpath: {
                type: String
            },

            images: {
                type: Array|String,
                required: false,
                default: () => ([])
            },

            multiple: {
                type: Boolean,
                required: false,
                default: true
            },
            removed: {
                type: Boolean,
                required: false,
                default: true
            },

            required: {
                type: Boolean,
                required: false,
                default: false
            }
        },

        data: function() {
            return {
                imageCount: 0,
                items: []
            }
        },
        watch : {
            testProp: function(event) {
                console.log('watch : '+event);
                if(event){
                    this.createFileTypeNew();
                }

            }
        },
        computed : {
            testProp: function() {
                console.log('compute images : '+this.images);
                if(!this.images){
                    this.createFileType();
                }

                return this.images
            }
        },
        // mounted () {
        //     this.createFileTypeNew();
        // },
        methods: {
            createFileTypeNew () {
                var this_this = this;

                if(!this.multiple) {
                    this.items.forEach(function(image) {
                        console.log('del');
                        console.log(image);
                        this_this.removeImageAll(image)
                    });
                }

                console.log(this.images);

                if(this.images && this.images !== '') {
                    this.items.push({'id': 'image_' + this.imageCount, 'url': this.imgpath + this.images})

                    this.imageCount++;
                }else{
                    this.createFileType();
                }


            },
            createFileType () {

                this.items.push({'id': 'image_' + this.imageCount});

                this.imageCount++;

            },

            removeImage (image) {

                if(image.hasOwnProperty("url")){
                    let index = this.items.indexOf(image)
                    Vue.delete(this.items, index);
                    this.$emit('clear');
                    this.$emit('upload');
                }

            },
            removeImageAll (image) {
                    console.log('remove all');
                    console.log(image);
                    let index = this.items.indexOf(image)
                    Vue.delete(this.items, index);
                    this.$emit('clear');
                    this.$emit('upload');

            },
            changeUpload (value) {
                this.$emit('upload',value);
            }
        }

    }
</script>
