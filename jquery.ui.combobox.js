$.widget("custom.combobox", {
    _create: function() {
        this.wrapper = $("<span>")
            .addClass("custom-combobox")
            .insertAfter(this.element);

        this.element.hide();
        this._createAutocomplete();
    },

    _createAutocomplete: function() {
        let selected = this.element.children(":selected"),
            value = selected.val() ? selected.text() : "";

        this.input = $("<input>")
            .appendTo(this.wrapper)
            .val(value)
            .attr("title", "")
            .addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
            .autocomplete({
                delay: 0,
                minLength: 0,
                source: this._source.bind(this)
            });

        this._on(this.input, {
            autocompleteselect: function(event, ui) {
                ui.item.option.selected = true;
                this._trigger("select", event, {
                    item: ui.item.option
                });
            },
        });
    },

    _source: function(request, response) {
        let matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
        response(this.element.children("option").map(function() {
            let text = $(this).text();
            if (this.value && (!request.term || matcher.test(text))) {
                return {
                    label: text,
                    value: text,
                    option: this
                };
            }
        }));
    }
});
