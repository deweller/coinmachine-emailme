// Generated by CoffeeScript 1.7.1
(function() {
  (function($) {
    var AccountSocket, BID_EL_HEIGHT, bidEntries, formatCurrency, formatTime, formatValueByElementSettings, init, numeral, rebuildNotifications, toggleShowHide, updateNotificationPreferences, updateNotifications, updatePayment;
    bidEntries = {};
    BID_EL_HEIGHT = 66;
    numeral = window.numeral;
    AccountSocket = window.AccountSocket = {};
    AccountSocket.connect = function(refId) {
      var socket;
      socket = window.io.connect();
      socket.on('status', function(data) {});
      socket.on('account-update', function(data) {
        setTimeout(function() {
          if (data.type === 'payment') {
            updatePayment(data);
          }
          if (data.type === 'notifications') {
            return updateNotifications(data);
          }
        }, 1);
      });
      socket.on('disconnect', function() {});
      socket.on('connect', function() {
        socket.emit('listen', refId);
      });
      return socket.on('error', function(e) {
        return console.error("ERROR", e.stack);
      });
    };
    init = function() {};
    updatePayment = function(data) {
      var amount, el, fieldName, type, value, _i, _len, _ref, _ref1, _results;
      _ref = ['notificationsRemaining'];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        fieldName = _ref[_i];
        el = $("*[data-field=\"" + fieldName + "\"]");
        if (!el.length) {
          continue;
        }
        value = formatValueByElementSettings(data[fieldName], el);
        el.html(value);
      }
      if (data.isLifetime && $('*[data-islifetime="no"]').is(':visible')) {
        $('.received').show();
        $('#AccountDetails').removeClass('is-trial').addClass('is-paid');
        $('*[data-islifetime="no"]').fadeOut('fast', function() {
          $('*[data-islifetime="yes"]').fadeIn('fast');
          return $('#AccountDetails').addClass('is-paid').removeClass('is-trial');
        });
      }
      if (!data.isLifetime && $('*[data-islifetime="yes"]').is(':visible')) {
        $('*[data-islifetime="yes"]').fadeOut('fast', function() {
          return $('*[data-islifetime="no"]').fadeIn('fast');
        });
      }
      updateNotificationPreferences(data);
      $('div.balances').empty();
      _ref1 = data.balance;
      _results = [];
      for (type in _ref1) {
        amount = _ref1[type];
        _results.push($('div.balances').append("<div class=\"balance\">Received " + (formatCurrency(amount)) + " " + type + "</div>"));
      }
      return _results;
    };
    updateNotificationPreferences = function(data) {
      var possibleValue, _fn, _i, _len, _ref;
      _ref = [0, 1, 3, 6];
      _fn = function() {
        var checkedEl, isChecked, notCheckedEl, settingEl, shouldBeChecked;
        settingEl = $("*[data-confirmations-number=\"" + possibleValue + "\"]");
        shouldBeChecked = !!data.confirmationsToSendMap[possibleValue];
        settingEl.data('is-checked', shouldBeChecked ? 'yes' : 'no');
        checkedEl = $('i.yes', settingEl);
        isChecked = checkedEl.is(':visible');
        if (isChecked !== shouldBeChecked) {
          notCheckedEl = $('i.no', settingEl);
          if (shouldBeChecked) {
            return notCheckedEl.fadeOut('fast', function() {
              return checkedEl.fadeIn('fast');
            });
          } else {
            return checkedEl.fadeOut('fast', function() {
              return notCheckedEl.fadeIn('fast');
            });
          }
        }
      };
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        possibleValue = _ref[_i];
        _fn();
      }
    };
    updateNotifications = function(data) {
      var show;
      rebuildNotifications(data.notifications);
      show = data.notifications.length > 0;
      toggleShowHide(show, 'data-has-notifications');
    };
    toggleShowHide = function(show, selector) {
      if (show && $("*[" + selector + "=\"no\"]").is(':visible')) {
        $("*[" + selector + "=\"no\"]").fadeOut('fast', function() {
          return $("*[" + selector + "=\"yes\"]").fadeIn('fast');
        });
      }
      if (!show && $("*[" + selector + "=\"yes\"]").is(':visible')) {
        return $("*[" + selector + "=\"yes\"]").fadeOut('fast', function() {
          return $("*[" + selector + "=\"no\"]").fadeIn('fast');
        });
      }
    };
    formatCurrency = function(amount) {
      if (amount == null) {
        return '';
      }
      if (isNaN(amount)) {
        return '';
      }
      return numeral(amount / 100000000).format('0,0.[00000000]');
    };
    formatTime = function(ms) {
      var m;
      m = window.moment(ms);
      return '<span class="time">' + m.format("M.DD.YYYY h:mm A") + ' <span class="tz">' + m.format('Z') + '</span>' + '</span>';
    };
    rebuildNotifications = function(notifications) {
      var html, notification, txLink, _i, _len;
      html = '';
      for (_i = 0, _len = notifications.length; _i < _len; _i++) {
        notification = notifications[_i];
        if (notification.tx.tx_hash.substr(0, 1) === 'M') {
          txLink = '';
        } else {
          txLink = "<span class=\"tx-link right\">\n    <a href=\"https://blockchain.info/tx/" + notification.tx.tx_hash + "\" target=\"_blank\" data-receipt-field=\"transactionLink\">View Transaction <i class=\"fa fa-external-link\"></i></a>\n</span>";
        }
        html += "<div class=\"notification\">\n    <div class=\"payment-section left\">\n        <span class=\"date\">\n            <span>" + (formatTime(parseInt(notification.sentDate, 10) * 1000)) + "</span>\n        </span>\n        <span class=\"confirmations\">\n            " + notification.confirmations + " confirmation" + (notification.confirmations === 1 ? '' : 's') + "\n        </span>\n    </div>\n    <span class=\"payment\">\n        <i class=\"fa fa-arrow-right\"></i> Received " + (formatCurrency(notification.tx.quantity)) + " " + notification.tx.asset + "\n    </span>\n    " + txLink + "\n</div>";
      }
      $('.notification-list').empty().append(html);
    };
    formatValueByElementSettings = function(value, el) {
      var formatter;
      if (!el.length) {
        return value;
      }
      formatter = el.data('formatter');
      switch (formatter) {
        case "bool":
          if (value) {
            value = "Yes";
            el.addClass('yes').removeClass('no');
          } else {
            value = "No";
            el.addClass('no').removeClass('yes');
          }
          break;
        case "currency":
          value = formatCurrency(value);
      }
      return value;
    };
    return init();
  })(jQuery);

}).call(this);
