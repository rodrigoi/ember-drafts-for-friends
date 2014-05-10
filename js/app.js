(function (){
  'use strict';

  window.ED = Ember.Application.create({
    rootElement: '.wrap',
    LOG_ACTIVE_GENERATION: true,
    LOG_VIEW_LOOKUPS: true,
    LOG_TRANSITIONS: true,
    LOG_TRANSITIONS_INTERNAL: true
  });

  ED.Router.reopen({
    location: 'none'
  });

  ED.ApplicationAdapter = DS.RESTAdapter.extend({
    host: draftsForFriends.ajax_endpoint,
    buildURL: function (type, id) {
      if(!id) {
        type = Ember.String.pluralize(type);
        return 'http://local.wordpress.dev/wp-admin/admin-ajax.php?action=ember_drafts_for_friends&type=' + type;
      }
      var url = 'http://local.wordpress.dev/wp-admin/admin-ajax.php?action=ember_drafts_for_friends&type=' + type;
      if(id) {
        url += "&id=" + id;
      }
      return url;
    }
  });

  ED.PostSerializer = DS.RESTSerializer.extend({
    primaryKey: 'ID'
  });

  ED.Post = DS.Model.extend({
    post_title: DS.attr('string'),
    post_status: DS.attr('string'),
    post_status_category: DS.attr('string')
  });

  ED.Draft = DS.Model.extend({
    post_id: DS.attr('number'),
    user_id: DS.attr('number'),
    hash: DS.attr('string'),
    post_title: DS.attr('string'),
    created_date: DS.attr('date'),
    expiration_date: DS.attr('date'),

    share_url: function () {
      return draftsForFriends.ajax_endpoint + "/?p=" + this.get('post_id') + "&draftsforfriends=" + this.get('hash');
    }.property('post_id', 'hash')
  });

  ED.IndexRoute = Ember.Route.extend({
    model: function() {
      return Ember.RSVP.hash({
        posts: this.store.find('post'),
        drafts: this.store.find('draft'),
        units: Ember.RSVP.resolve([
          {title: 'seconds', value: 's'},
          {title: 'minutes', value: 'm'},
          {title: 'hours', value: 'h'},
          {title: 'days', value: 'd'}
        ])
      });
    }
  });

  ED.IndexController = Ember.ObjectController.extend({
    actions: {
      createDraft: function (){
        var model = this.get('model');

        var draft = ED.Draft.create({
          post_id: model.post_id,
          expiration: model.expiration,
          unit: model.unit
        });

        console.log(draft);
        draft.save();
      }
    }
  });

  ED.PostController = Ember.ObjectController.extend({
    actions: {
      delete: function (){
        var post = this.get('model');
        post.deleteRecord();
        post.save();
      },
      extendLimit: function (){
        
      }
    }
  });

  ED.IndexView = Ember.View.extend({
    didInsertElement: function (){
      var _this = this;
      var client = new ZeroClipboard( _this.$( '.copy-to-clipboard' ) );

      client.on( 'mouseover', function ( e ) {
        var target = _this.$( e.target );
        target.parents( 'div.row-actions' ).addClass('visible');
      });

      client.on( 'mouseout', function ( e ) {
        var target = _this.$( e.target );
        target.parents( 'div.row-actions' ).removeClass('visible');
      });

      client.on( 'aftercopy', function ( e ) {
        var target = _this.$( e.target );
        var message = target.parents('.post_title').find('.copied');

        message.addClass( 'show' );

        setTimeout(function (){
          message.removeClass( 'show' );
        }, 1000);

      });

    }
  });

  ED.CreateView = Ember.View.extend({
    templateName: 'create'
  });

})();
