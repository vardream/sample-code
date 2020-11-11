<template>
  <div class="comment__items" v-show="totalPages > 0">
    <h3 v-if="contentType === 'product'">Отзывы</h3>
    <h3 v-else>Комментарии</h3>
    <div class="comment__item" v-for="item in items">
      <div class="comment__body" v-html="item.body"></div>
      <div class="comment__info">
        <p class="comment__date">{{ item.date }}</p>
        <p class="comment__author">{{ item.person }}</p>
      </div>
    </div>

    <pagination
        v-show="totalPages > 1"
        :current="currentPage"
        :totalPages="totalPages"
        @page-changed="fetchComments">
    </pagination>

  </div>
</template>

<script>
  import Pagination from "./Pagination.vue";

  export default {
    name: "comments-list",
    props: {
      contentType: {
        type: String,
        default: ""
      },
      contentItem: {
        type: Number,
        default: 0
      }
    },
    components: {
      Pagination
    },
    data() {
      return {
        state: "default",
        items: [],
        totalPages: 0,
        currentPage: 1
      }
    },
    methods: {
      fetchComments(page) {
        if (this.contentItem > 0 && this.contentType !== "") {
          $.ajax({
            url: '/api/comment/' + this.contentType + '/' + this.contentItem + '/page-' + page,
            type: 'GET',
            data: '',
            dataType: 'json',
            // успех
            success: (data, textStatus) => {
              this.state = "success";
              this.totalPages = data.hasOwnProperty('total_pages') ? data.total_pages : 0;
              this.currentPage = data.hasOwnProperty('page_current') ? data.page_current : 1;
              this.items = data.hasOwnProperty('items') ? data.items : [];
            },
            // ошибка
            error: (jqXHR, textStatus, errorThrown) => {
              this.state = "error";
            }
          });
        } else {
          this.state = "error";
        }
      }
    },
    created() {
      this.fetchComments(this.currentPage);
    }
  }
</script>

<style>

</style>
