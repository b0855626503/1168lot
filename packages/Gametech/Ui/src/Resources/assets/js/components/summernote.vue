<template>
  <div>
    <textarea :id="id" :value="value" @change="onChange" :required="required" :name="name" ref="editor"
              class="editor"></textarea>
  </div>
</template>

<script>


export default {
  name: 'summernote',
  props: {
    required: {
      type: Boolean,
      required: false,
      default: false
    },
    value: {
      type: String
    },
    name: {
      type: String
    },
    placeholder: {
      type: String
    },
    autofocus: {
      type: String
    }
  },
  data() {
    return {
      height: 'auto',
      minHeight: false
    }
  },
  watch: {
    value(val) {
      if (this.summernote.summernote('code') !== val) {
        //this.summernote.summernote("editor.rewind")
        this.summernote.summernote('code', val)
      }
    }
  },
  computed: {
    id() {
      return "sn_" + Date.now()
    }
  },

  mounted() {
    this.summernote = $(this.$refs.editor).summernote({
      dialogsInBody: false,
      height: "250px", minHeight: "200px",
      focus: typeof this.autofocus !== 'undefined',
      placeholder: this.placeholder,
      popover: {
        image: [
          ['custom', ['imageAttributes']],
          ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
          ['float', ['floatLeft', 'floatRight', 'floatNone']],
          ['remove', ['removeMedia']]

        ]
      },
      lang: 'en-US',
      imageAttributes:{
        icon:'<i class="note-icon-pencil"/>',
        removeEmpty:false, // true = remove attributes | false = leave empty if present
        disableUpload: false // true = don't display Upload Options | Display Upload Options
      },
      callbacks: {
        onImageUpload: this.convertToDataURI,
        onDialogShown: function() {
          $(".note-modal-backdrop").hide();
          $(".modal-backdrop").hide();
        }
      }
    })

    this.summernote.on('summernote.change', this.onChange)
    $("#" + this.id).next().find(".note-toolbar").find("[data-toggle=dropdown]").attr('onclick', '$(this).next().toggle()')
        .next().attr('onclick', '$(this).toggle()')
  },
  methods: {
    convertToDataURI(files) {
      console.log('convertToDataURI');
      let reader = new FileReader();
      reader.readAsDataURL(files[0]);
      reader.onload = (e) => {
        let img = new Image();
        img.src = e.target.result;
        img.onload = () => {
          let canvas = document.createElement("canvas");
          let ctx = canvas.getContext("2d");

          let maxWidth = 800;
          let maxHeight = 600;
          let width = img.width;
          let height = img.height;

          if (width > maxWidth || height > maxHeight) {
            let scale = Math.min(maxWidth / width, maxHeight / height);
            width *= scale;
            height *= scale;
          }

          canvas.width = width;
          canvas.height = height;
          ctx.drawImage(img, 0, 0, width, height);
          console.log('insertImage');
          let dataURI = canvas.toDataURL("image/webp", 0.7); // แปลงเป็น Base64 และลดขนาด
          $(this.$refs.editor).summernote("insertImage", dataURI, function ($image) {
            // $image.css('width', $image.width() / 3);
            // $image.attr('data-filename', 'retriever');
            $image.removeAttr('style');
            $image.addClass('img-fluid');

          });


        };
      };

    },
    onChange(we) {
      this.$emit('input', we.target.value)
    },
    onSubmit: function () {
      $(this.$refs.editor).summernote('focus')
    },
    removeImageStyles(e) {
      $(e.target).removeAttr('style');  // ลบ style ที่ Summernote ใส่ให้
      $(e.target).addClass('img-fluid');
    }
  }
};

</script>
