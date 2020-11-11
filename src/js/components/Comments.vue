<template>
  <div>
    <comments-list
        :content-type="contentType"
        :content-item="contentItem"
    />

    <send-comment
        :content-type="contentType"
        :content-item="contentItem"
    />
  </div>
</template>

<script>
  import CommentsList from './CommentsList.vue';
  import SendComment from './SendComment.vue';

  export default {
    name: "comments",
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
      CommentsList,
      SendComment
    }
  }
</script>

<style/>
