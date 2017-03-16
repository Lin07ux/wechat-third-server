/**
 * Created by Lin07ux on 2017/1/20.
 */

Vue.http.options.emulateJSON = true;

new Vue({
    el: '#articleList',
    data: {
        loading: false,
        loadMsg: '',
        tab: 'lists',
        showForm: false,
        form: {},
        current: {},
        lists: [],
        limit: 0,
        curList: 0,
        articles: {
            lists: [],
            total: 0,
            pages: 0,
            page: 1,
            rows: 15
        },
        search: {
            show: false,
            searching: false,
            text: '',
            results: []
        }
    },
    created: function () { this.loadList(); },
    watch: {
        curList: function () {
            if (this.curList <= 0) return false;

            this.loadArticles(true);
        }
    },
    methods: {
        // 加载文章列表
        loadList: function () {
            var self = this;
            this.ajaxGet(urls.lists, {}, '加载列表中...', function (data) {
                self.lists = data.lists;
                self.limit = data.limit;
            });
        },

        // 生成列表的 URL
        genListUrl: function (id) {
            return window.location.origin + '/ArticleList/' + id;
        },

        // 查看列表详情
        setCurList: function (id) {
            this.curList = id;
            this.tab = 'detail';
        },

        // 添加列表
        addList: function () {
            if (this.lists.length >= this.limit) {
                return alert('最多只能添加' + this.limit + '个文章列表');
            }

            this.form = { id: 0, name: '' };
            this.showForm = true;
        },

        // 编辑列表
        editList: function (list) {
            this.current = list;
            this.form.id = list.id;
            this.form.name = list.name;
            this.showForm = true;
        },

        // 删除列表
        removeList: function (list, index) {
            if (!confirm('你确定要删除该文章列表吗？')) return false;

            var self = this;
            this.ajaxPost(urls.remove, { id: list.id }, '删除中...', function () {
                self.lists.splice(index, 1);
            });
        },

        // 保存列表
        submitList: function () {
            if (!this.form.name) return alert('请填写列表的名称');

            var self = this;
            this.ajaxPost(urls.handle, this.form, '保存中...', function (data) {
                if (self.form.id) {
                    self.current.name = self.form.name;
                } else {
                    self.lists.unshift({ id: data, name: self.form.name });
                }

                self.showForm = false;
            });
        },

        // 加载列表中的文章
        loadArticles: function (firstPage) {
            var articles = this.articles;

            var vm = this;
            var msg = '加载列表的文章中...';
            var params = {
                list: this.curList,
                page: firstPage ? 1 : articles.page
            };
            this.ajaxGet(urls.articles, params, msg, function (data) {
                Object.assign(articles, data);
                vm.scrollTop();
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

        // 搜索文章
        searchArticle: function () {
            var search = this.search;

            if (!search.text) return false;

            search.searching = true;

            var params = { keyword: search.text, list: true };
            this.ajaxGet(urls.search, params, false, function (data) {
                search.results = data;
            }).then(function () {
                setTimeout(function () { search.searching = false }, 200)
            });
        },

        // 添加文章
        addArticle: function (article, index) {
            var msg = '添加中...';
            var data = {
                article: article.id,
                list: this.curList
            };

            var vm = this;
            this.ajaxPost(urls.addArticle, data, msg, function (data) {
                article.detail = data;
                vm.addToLists(article);
                vm.search.results.splice(index, 1);
                vm.search.show = false;
            })
        },

        // 向当前列表中添加文章
        addToLists: function (article) {
            var a = this.articles;

            a.lists.unshift(article);
            a.total++;
            a.pages = Math.ceil(a.total / a.rows);
        },

        // 删除列表中的文章
        delArticle: function (article, index) {
            if (!confirm('确定要从列表中删除该文章吗？')) return false;

            var data = { id: article.detail };
            var vm = this;
            this.ajaxPost(urls.delArticle, data, '删除中...', function () {
                vm.delFromLists(index);
                alert('删除成功');
            })
        },

        // 从当前列表中移除文章
        delFromLists: function (index) {
            var a = this.articles;

            a.lists.splice(index, 1);
            a.total--;
            a.pages = Math.ceil(a.total / a.rows);
        },

        // 列表滚动到顶部
        scrollTop: function () {
            this.$nextTick(function () {
                var parent = this.$el.parentElement;

                parent && (parent.scrollTop = 0);
            });
        },

        // ajax get
        ajaxGet : function (url, params, loadMsg, success) {
            this.loadMsg = loadMsg || '';
            this.loading = !!loadMsg;

            var self = this;
            return this.$http.get(url, { params: params}).then(function (response) {
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
            this.loading = !!loadMsg;

            var self = this;
            return this.$http.post(url, body).then(function (response) {
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
