/**
 * Created by Lin07ux on 2017/1/20.
 */

// Object.assign Polyfill
if (typeof Object.assign != 'function') {
    Object.assign = function(target, varArgs) {
        'use strict';
        if (target == null) { // TypeError if undefined or null
            throw new TypeError('Cannot convert undefined or null to object');
        }

        var to = Object(target);
        var length = arguments.length;

        for (var index = 1; index < length; index++) {
            var nextSource = arguments[index];

            if (nextSource != null) { // Skip over if undefined or null
                for (var nextKey in nextSource) {
                    // Avoid bugs when hasOwnProperty is shadowed
                    if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                        to[nextKey] = nextSource[nextKey];
                    }
                }
            }
        }

        return to;
    };
}

Vue.config.devtools = true;
Vue.http.options.emulateJSON = true;

new Vue({
    el: '#articles',
    data: {
        loading: false,
        loadMsg: '',
        articles: {
            lists: [],
            total: 0,
            pages: 0,
            page: 1,
            rows: 20
        },
        showForm: false,
        form: {},
        current: {},
        editor: null,
        placeholder: '',
        loadCover: false,
        loadThumb: false
    },
    created: function () {
        this.loadArticles();
    },
    mounted: function () {
        // 初始化文章内容编辑器
        var editor = new wangEditor('article-content');
        editor.config.menus = [
            "source","|","bold","underline","italic","strikethrough",
            "eraser", "forecolor", "bgcolor","|","quote","fontfamily",
            "fontsize","head","unorderlist","orderlist","alignleft",
            "aligncenter","alignright","|","link","unlink","table",
            "|","undo","redo","fullscreen"
        ];
        editor.create();

        this.placeholder = $('#article-content').attr('placeholder');
        this.editor = editor;
    },
    methods: {
        // 加载文章列表
        loadArticles: function () {
            var msg = '加载文章列表中...';
            var params = { page: this.articles.page };
            var self = this;

            this.ajaxGet(urls.lists, params, msg, function (data) {
                Object.assign(self.articles, data);
            });
        },

        // 上一页
        prevPage: function () {
            if (this.articles.page > 1) {
                this.articles.page--;
                this.loadArticles();
            }
        },
        // 下一页
        nextPage: function () {
            if (this.articles.page < this.articles.pages) {
                this.articles.page++;
                this.loadArticles();
            }
        },
        // 跳转页面
        page: function () {
            var articles = this.articles;

            if (articles.page >= 1 && articles.page <= articles.pages) {
                this.loadArticles();
            }
        },

        resetForm: function () {
            this.form = {
                id: 0,
                type: 0,
                title: '',
                cover: '',
                thumb: '',
                desc: '',
                link: '',
                content: ''
            }
        },

        // 获取和设置产品描述编辑器中的内容
        getContent: function () {
            var text = this.editor.$txt.text();

            // 如果没有内容或者是默认内容则返回空
            if (!text || this.placeholder === text) return '';

            return this.editor.$txt.html();
        },
        setContent: function (html) {
            this.editor.$txt.html(html || '<p>'+this.placeholder+'</p>');
        },

        // 新建文章
        add: function () {
            this.resetForm();
            this.setContent();
            this.showForm = true;
        },

        // 编辑文章
        edit: function (article) {
            this.current = article;

            var msg = '加载文章信息中...';
            var self = this;
            this.ajaxGet(urls.detail, { id: article.id }, msg, function (data) {
                self.form = data;
                self.setContent(data.content);
                self.showForm = true;
            });
        },

        // 上传文章封面图片
        upload: function (e, isCover) {
            var input = e.target;
            if (!input.files.length) return false;

            var file = input.files[0];
            input.value = null;
            if (!/^image\/(jpe?g|png)$/.test(file.type)) {
                return alert('文件格式错误！需要为jpg、jpeg、png格式。');
            }

            if (file.size > 2 * 1024 * 1024) {
                return alert('图片大小不得超过 2M');
            }

            var fd = new FormData();
            fd.append('file', file);

            isCover ? (this.loadCover = true) : (this.loadThumb = true);

            var self = this;
            this.$http.post(urls.upload, fd).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                isCover ? (self.form.cover = res.data) : (self.form.thumb = res.data);
            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                isCover ? (self.loadCover = true) : (self.loadThumb = true);
            });
        },

        // 提交更新或新增的文章信息
        submit: function () {
            var form = this.form;

            // 检查文章封面
            if (!form.cover) return alert('请设置文章的封面图片');

            // 检查文章内容
            if (form.type == 1) {
                form.content = this.getContent();
                if (!form.content) return alert('请填写文章内容');
            } else {
                if (!form.link) return alert('请设置原文链接');
            }

            var url = form.id ? urls.edit : urls.add;
            var self = this;
            this.ajaxPost(url, form, '提交中...', function (data) {
                if (form.id) {
                    Object.assign(self.current, form);
                    alert('更新文章成功');
                } else {
                    form.id = data.id;
                    form.url = data.url;

                    var articles = self.articles;
                    articles.lists.unshift(Object.assign({}, form));
                    articles.total++;
                    articles.pages = Math.ceil(articles.total, articles.rows);

                    alert('添加文章成功');
                }

                self.showForm = false;
            });
        },

        // 删除文章
        remove: function (articleID, index) {
            if (!confirm('确定要删除该文章吗？')) return false;

            var articles = this.articles;
            var msg = '删除中...';
            this.ajaxPost(urls.remove, { id: articleID }, msg, function () {
                if (articles.total > 0) articles.total--;

                articles.pages = Math.ceil(articles.total / articles.rows);
                articles.lists.splice(index, 1);
            });
        },

        // ajax get
        ajaxGet : function (url, params, loadMsg, success) {
            this.loadMsg = loadMsg || '';
            this.loading = true;

            var self = this;
            this.$http.get(url, { params: params}).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data);

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        },
        // ajax post
        ajaxPost: function (url, body, loadMsg,  success) {
            this.loadMsg = loadMsg || '';
            this.loading = true;

            var self = this;
            this.$http.post(url, body).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data);

            }).catch(function (response) {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        }
    }
});
