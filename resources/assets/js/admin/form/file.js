Vue.component('element-file', Vue.extend({
    props: {
        url: {
            required: true
        },
        value: {
            default: ''
        },
        file: {
            default: ''
        },
        readonly: {
            type: Boolean,
            default: false
        },
        name: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            errors: []
        }
    },
    ready () {
        this.initUpload()
    },
    methods: {
        initUpload () {
            let self = this,
                container = $(self.$el.parentNode),
                button = container.find('.upload-button');

            button.dropzone({
                url: this.url,
                method: 'POST',
                uploadMultiple: false,
                previewsContainer: false,
                dictDefaultMessage: '',
                sending () {
                    self.closeAlert()
                },
                success (file, response) {
                    self.value = response.value;
                    self.file = response.path;
                },
                error (file, response) {
                    if(_.isArray(response.errors)) {
                        self.$set('errors', response.errors);
                    }
                }
            });
        },
        remove () {
            var self = this;

            Admin.Messages.confirm(trans('lang.message.are_you_sure')).then(() => {
                self.value = '';
                self.file = '';
            });
        },
        closeAlert () {
            this.$set('errors', []);
        }
    },
    computed: {
        has_value () {
            return this.value.length > 0
        },
        // file () {
        //     return ((this.value.indexOf('http') === 0) ? this.value : Admin.Url.upload(this.value))
        // },
        link () {
            return ((this.file.indexOf('http') === 0) ? this.file : Admin.Url.upload(this.file))
        }
    }
}));
