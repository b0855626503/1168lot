$(document).ready(function () {

    Vue.mixin({
        data: function () {
            return {
                'imageObserver': null,
                'baseUrl': document.getElementById("mainscript").getAttribute('baseUrl')

            }
        },
        methods: {
            redirect: function (route) {
                route ? window.location.href = route : '';
            },


            isMobile: function () {
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i | /mobi/i.test(navigator.userAgent)) {
                    if (this.isMaxWidthCrossInLandScape()) {
                        return false;
                    }
                    return true
                } else {
                    return false
                }
            },

            isMaxWidthCrossInLandScape: function () {
                return window.innerWidth > 900;
            },

            getDynamicHTML: function (input) {
                var _staticRenderFns;
                const {render, staticRenderFns} = Vue.compile(input);

                if (this.$options.staticRenderFns.length > 0) {
                    _staticRenderFns = this.$options.staticRenderFns;
                } else {
                    _staticRenderFns = this.$options.staticRenderFns = staticRenderFns;
                }

                try {
                    var output = render.call(this, this.$createElement);
                } catch (exception) {
                    console.log(this.__('error.something_went_wrong'));
                }

                this.$options.staticRenderFns = _staticRenderFns;

                return output;
            },

            getStorageValue: function (key) {
                let value = window.localStorage.getItem(key);

                if (value) {
                    value = JSON.parse(value);
                }

                return value;
            },

            setStorageValue: function (key, value) {
                window.localStorage.setItem(key, JSON.stringify(value));

                return true;
            },
        }
    });

    new Vue({
        el: "#app",
        data: function () {
            return {
                modalIds: {},
                showPageLoader: false,
            }
        },

        created: function () {
            setTimeout(() => {
                document.body.classList.remove("modal-open");
            }, 0);

            window.addEventListener('click', () => {
                let modals = document.getElementsByClassName('sensitive-modal');

                Array.from(modals).forEach(modal => {
                    modal.classList.add('hide');
                });
            });
        },

        mounted: function () {
            setTimeout(() => {
                this.addServerErrors();
                this.addFlashMessages();
            }, 0);


            this.$validator.localize(document.documentElement.lang);


            this.addIntersectionObserver();
        },

        methods: {

            onSubmit: function (e) {
                this.toggleButtonDisable(true);

                if (typeof tinyMCE !== 'undefined')
                    tinyMCE.triggerSave();

                this.$validator.validateAll().then(result => {
                    if (result) {
                        e.target.submit();
                    } else {
                        this.toggleButtonDisable(false);

                        eventBus.$emit('onFormError')
                    }
                });
            },

            toggleButtonDisable(value) {
                var buttons = document.getElementsByTagName("button");

                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = value;
                }
            },

            addServerErrors: function (scope = null) {
                for (var key in serverErrors) {
                    var inputNames = [];
                    key.split('.').forEach(function (chunk, index) {
                        if (index) {
                            inputNames.push('[' + chunk + ']')
                        } else {
                            inputNames.push(chunk)
                        }
                    })

                    var inputName = inputNames.join('');

                    const field = this.$validator.fields.find({
                        name: inputName,
                        scope: scope
                    });

                    if (field) {
                        this.$validator.errors.add({
                            id: field.id,
                            field: inputName,
                            msg: serverErrors[key][0],
                            scope: scope
                        });
                    }
                }
            },

            addFlashMessages: function () {
                for (let key in flashMessages) {
                    if (flashMessages[key].message)
                        Toast.fire({
                            icon: flashMessages[key].type,
                            title: flashMessages[key].message
                        })
                }
            },

            showModal: function (id) {
                this.$set(this.modalIds, id, true);
            },


            addIntersectionObserver: function () {
                this.imageObserver = new IntersectionObserver((entries, imgObserver) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target
                            lazyImage.src = lazyImage.dataset.src
                        }
                    })
                });
            },

            showLoader: function () {
                $('#loader').show();
                $('.overlay-loader').show();

                document.body.classList.add("modal-open");
            },

            hideLoader: function () {
                $('#loader').hide();
                $('.overlay-loader').hide();

                document.body.classList.remove("modal-open");
            },

            topFunction: function () {
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;
            },

            onOpen: function (route) {
                window.open(route);
            }

        }
    });

    Vue.component('vnode-injector', {
        functional: true,
        props: ['nodes'],
        render(h, {props}) {
            return props.nodes;
        }
    });

});
