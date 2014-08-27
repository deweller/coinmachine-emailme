do ($=jQuery) ->
    # FADE_SPEED = 75
    bidEntries = {}

    BID_EL_HEIGHT = 66


    numeral = window.numeral


    # ####################################################################################################################

    AccountDetails = window.AccountDetails = {}
    AccountDetails.refId = null
    AccountDetails.init = (refId)->
        AccountDetails.refId = refId
        return

    init = ()->
        $('a.confirmation-setting').on 'click', (e)->
            e.preventDefault()

            clearErrors()

            el = $(this)
            newValue = if el.data('is-checked') == 'yes' then false else true
            confirmationsNumber = el.data('confirmations-number')
            $.ajax({
                type: 'POST'
                url: "/account/confirmationSetting/#{AccountDetails.refId}.json"
                data: JSON.stringify({confirmationsNumber: confirmationsNumber, confirmationValue: newValue})
                success: (data)->
                    if not data.success
                        showErrors(data.errors)
                        return
                contentType: "application/json"
                dataType: 'json'
            })

        return

    showErrors = (errors)->

        html = """

            <div data-alert class="alert-box alert" tabindex="0" aria-live="assertive" role="dialogalert">
            <div>There were some errors:</div>
            <div class="error-messages"></div>
            </div>

        """

        newEl = $(html)
        for error in errors
            $('.error-messages', newEl).append("""<div class="error-message">#{error}</div>""")

        newEl.appendTo($('.error-container'))

        # trigger
        # newEl.foundation({alert: {speed: 401}})


        return

    clearErrors = ()->
        $('.alert','.error-container').fadeOut('fast')

    # ####################################################################################################################

    init()

