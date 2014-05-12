(function (){
  'use strict';

  window.EmberDrafts = Ember.Application.create({
    rootElement: '.wrap',
    LOG_ACTIVE_GENERATION: true,
    LOG_VIEW_LOOKUPS: true,
    LOG_TRANSITIONS: true,
    LOG_TRANSITIONS_INTERNAL: true
  });

  EmberDrafts.Router.reopen({
    location: 'none'
  });

  EmberDrafts.ApplicationAdapter = DS.RESTAdapter.extend({
    host: draftsForFriends.ajax_endpoint,
    buildURL: function (type, id) {
      if(!id) {
        type = Ember.String.pluralize(type);
        return this.host + '?action=ember_drafts_for_friends&type=' + type;
      }
      var url = this.host + '?action=ember_drafts_for_friends&type=' + type;
      if(id) {
        url += "&id=" + id;
      }
      return url;
    }
  });

  EmberDrafts.Post = DS.Model.extend({
    post_title: DS.attr('string'),
    post_status: DS.attr('string'),
    post_status_category: DS.attr('string')
  });

  EmberDrafts.PostSerializer = DS.RESTSerializer.extend({
    primaryKey: 'ID'
  });

  EmberDrafts.Draft = DS.Model.extend({
    post_id: DS.attr('number'),
    user_id: DS.attr('number'),
    hash: DS.attr('string'),
    post_title: DS.attr('string'),
    created_date: DS.attr('date'),
    expiration: DS.attr('number'),
    expiration_unit: DS.attr('string'),
    expiration_date: DS.attr('date'),

    share_url: function () {
      return draftsForFriends.blog_url + "/?p=" + this.get('post_id') + "&emberdraftsforfriends=" + this.get('hash');
    }.property('post_id', 'hash')
  });

  EmberDrafts.IndexRoute = Ember.Route.extend({
    model: function() {
      return Ember.RSVP.hash({
        posts: this.store.find('post'),
        drafts: this.store.find('draft')
      });
    }
  });

  EmberDrafts.CreateController = Ember.ObjectController.extend({
    units: [
      {title: 'seconds', value: 's'},
      {title: 'minutes', value: 'm'},
      {title: 'hours', value: 'h'},
      {title: 'days', value: 'd'}
    ],
    form: {
      expiration: 2,
      expiration_unit: 'h'
    },
    actions: {
      create: function (){
        var draft = this.store.createRecord('draft', this.get('form'));
        return draft.save();
      }
    }
  });
  
  EmberDrafts.PostController = Ember.ObjectController.extend({
    actions: {
      delete: function (){
        var post = this.get('model');
        post.deleteRecord();
        return post.save();
      }
    }
  });

  EmberDrafts.CopyToClipboardComponent = Ember.Component.extend({
    tagName: 'a',
    classNames: ['copy-to-clipboard'],
    attributeBindings: ['data-clipboard-text'],
    didInsertElement: function () {
      var _this = this;
      var clip = new ZeroClipboard(this.$());

      clip.on('mouseover', function (e) {
        var target = _this.$(e.target);
        target.parents('div.row-actions').addClass('visible');
      });

      clip.on('mouseout', function (e) {
        var target = _this.$(e.target);
        target.parents('div.row-actions').removeClass('visible');
      });

      clip.on('aftercopy', function (e) {
        var target = _this.$( e.target );
        var message = target.parents('.title').find('.copied');

        message.addClass('show');

        Ember.run.later(function (){
          message.removeClass('show');
        }, 1000);
      });
    }
  });

  Ember.Handlebars.registerBoundHelper('humanize', function(value, options) {
    var created_date = moment(value);
    return moment.duration(created_date.diff(moment.utc())).humanize();
  });

})();
