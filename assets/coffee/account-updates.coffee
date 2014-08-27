do ($=jQuery) ->
    # FADE_SPEED = 75
    bidEntries = {}

    BID_EL_HEIGHT = 66


    numeral = window.numeral


    # ####################################################################################################################

    AccountSocket = window.AccountSocket = {}
    AccountSocket.connect = (refId)->

        socket = window.io.connect()

        socket.on 'status', (data)->
            # console.log('status: '+data.state)
            return

        socket.on 'account-update', (data)->
            console.log "update",data
            setTimeout ()->
                if data.type == 'payment'
                    updatePayment(data)
                if data.type == 'notifications'
                    updateNotifications(data)
            , 1
            return

        socket.on 'disconnect', ()->
            # console.log('disconnected from server')
            return

        socket.on 'connect', ()->
            # console.log('state: connected')
            socket.emit 'listen', refId
            return

        socket.on 'error', (e)->
            console.error "ERROR",e.stack

    init = ()->
        return


    # ####################################################################################################################

    updatePayment = (data)->
        console.log "updatePayment data",data

        # console.log "updatePageVars (3)"
        for fieldName in ['notificationsRemaining',]
            el = $("""*[data-field="#{fieldName}"]""")
            continue if not el.length
            value = formatValueByElementSettings(data[fieldName], el)
            el.html(value)

        # check isLifetime change
        if data.isLifetime and $('*[data-islifetime="no"]').is(':visible')
            $('.received').show()
            $('#AccountDetails').removeClass('is-trial').addClass('is-paid')
            $('*[data-islifetime="no"]').fadeOut 'fast', ()->
                $('*[data-islifetime="yes"]').fadeIn('fast')
        if not data.isLifetime and $('*[data-islifetime="yes"]').is(':visible')
            $('*[data-islifetime="yes"]').fadeOut 'fast', ()->
                $('*[data-islifetime="no"]').fadeIn('fast')

        # update notifications
        updateNotificationPreferences(data)

        # rebuild balances
        $('div.balances').empty()
        for type, amount of data.balance
            $('div.balances').append("""<div class="balance">Received #{formatCurrency(amount)} #{type}</div>""")


    updateNotificationPreferences = (data)->
        # <a class="confirmation-setting" data-confirmations-number="0">
        #     <i style="display: {{true?'inline':'none'}};" class="fa fa-times no"></i>
        #     <i style="display: {{false?'inline':'none'}};" class="fa fa-check yes"></i>
        #     Immediately
        # </a>
        for possibleValue in [0,1,3,6]
            do ()->
                settingEl = $("""*[data-confirmations-number="#{possibleValue}"]""")
                shouldBeChecked = !!data.confirmationsToSendMap[possibleValue]
                settingEl.data('is-checked', if shouldBeChecked then 'yes' else 'no')
                checkedEl = $('i.yes', settingEl)
                isChecked = checkedEl.is(':visible')

                if isChecked != shouldBeChecked
                    notCheckedEl = $('i.no', settingEl)
                    if shouldBeChecked
                        notCheckedEl.fadeOut 'fast', ()->
                            checkedEl.fadeIn('fast')
                    else
                        checkedEl.fadeOut 'fast', ()->
                            notCheckedEl.fadeIn('fast')
        return
        


    # ####################################################################################################################

    updateNotifications = (data)->
        # build the notifications list
        rebuildNotifications(data.notifications)

        # toggle data-has-notifications
        show = (data.notifications.length > 0)
        toggleShowHide(show, 'data-has-notifications')


        return

    toggleShowHide = (show, selector)->
        if show and $("""*[#{selector}="no"]""").is(':visible')
            $("""*[#{selector}="no"]""").fadeOut 'fast', ()->
                $("""*[#{selector}="yes"]""").fadeIn('fast')

        if not show and $("""*[#{selector}="yes"]""").is(':visible')
            $("""*[#{selector}="yes"]""").fadeOut 'fast', ()->
                $("""*[#{selector}="no"]""").fadeIn('fast')

    formatCurrency = (amount)->
        # console.log "amount=",amount
        return '' if not amount?
        return '' if isNaN(amount)
        return numeral(amount / 100000000).format('0,0.[00000000]')

    formatTime = (ms)->
        m = window.moment(ms)
        return '<span class="time">'+m.format("M.DD.YYYY h:mm A")+' <span class="tz">'+m.format('Z')+'</span>'+'</span>'


    # ###################################################
    # Notifications

    rebuildNotifications = (notifications)->
        html = ''
        for notification in notifications
            html += """
            <div class="notification">
                <div class="payment-section left">
                    <span class="date">
                        <span>#{formatTime(parseInt(notification.sentDate, 10) * 1000)}</span>
                    </span>
                    <span class="confirmations">
                        #{notification.confirmations} confirmation#{if notification.confirmations == 1 then '' else 's'}
                    </span>
                </div>
                <span class="payment">
                    <i class="fa fa-arrow-right"></i> Received #{formatCurrency(notification.tx.quantity)} #{notification.tx.asset}
                </span>
                <span class="tx-link right">
                    <a href="https://blockchain.info/tx/#{notification.tx.tx_hash}" target="_blank" data-receipt-field="transactionLink">View Transaction <i class="fa fa-external-link"></i></a>
                </span>
            </div>
            """
        $('.notification-list').empty().append(html)
        return
                    

    # ###################################################
    # formatter
    
    formatValueByElementSettings = (value, el)->
        return value if not el.length
        # console.log "formatValueByElementSettings"
        formatter = el.data('formatter')
        switch formatter
            when "bool"
                if value
                    value = "Yes"
                    el.addClass('yes').removeClass('no')
                else
                    value = "No"
                    el.addClass('no').removeClass('yes')
            when "currency"
                value = formatCurrency(value)

        return value


    # ###################################################
    # Admin


    # ####################################################################################################################

    init()

