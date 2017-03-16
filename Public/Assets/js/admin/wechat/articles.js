/**
 * Created by Lin07ux on 2017/1/20.
 */

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
            rows: 15
        },
        showForm: false,
        form: {},
        current: {},
        editArticle: edit || 0,
        editor: null,
        placeholder: '',
        loadCover: false,
        loadThumb: false
    },
    created: function () {
        if (this.editArticle > 0) {
            this.edit(this.editArticle, true);
        } else {
            this.loadArticles();
        }
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

        // 生产文章的前台访问URL
        genArticleUrl: function (id) {
            return window.location.origin + '/Article/' + id;
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
                content: '',
                publish_time: ''
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
        edit: function (article, isEditModel) {
            var body;

            if (isEditModel) {
                body = { id: article };
            } else {
                this.current = article;
                body = { id: article.id }
            }

            var msg = '加载文章信息中...';
            var self = this;
            this.ajaxGet(urls.detail, body, msg, function (data) {
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
                isCover ? (self.loadCover = false) : (self.loadThumb = false);
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

            var self = this;
            this.ajaxPost(urls.handle, form, '提交中...', function (data) {
                if (form.id > 0) {
                    Object.assign(self.current, form);
                    alert('更新文章成功');
                } else {
                    form.id = data;

                    var articles = self.articles;
                    articles.lists.unshift(Object.assign({}, form));
                    articles.total++;
                    articles.pages = Math.ceil(articles.total, articles.rows);

                    alert('添加文章成功');
                }

                self.saveSuccess()
            });
        },

        // 保存成功之后根据当前是否是编辑模式而做不同的动作
        saveSuccess: function () {
            if (this.editArticle) {
                if (window.history.length) {
                    window.history.go(-1);
                } else {
                    window.location.href = 'index';
                }
            } else {
                this.showForm = false;
            }
        },

        // 删除文章
        remove: function (articleID, index) {
            if (!confirm('确定要删除该文章吗？\n这将会从所有文章列表中删除该文章。'))
                return false;

            var articles = this.articles;
            var msg = '删除中...';
            this.ajaxPost(urls.remove, { id: articleID }, msg, function () {
                if (articles.total > 0) articles.total--;

                articles.pages = Math.ceil(articles.total / articles.rows);
                articles.lists.splice(index, 1);

                alert('删除成功');
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

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        }
    }
});
