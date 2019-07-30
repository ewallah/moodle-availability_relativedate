YUI.add('moodle-availability_relativedate-form', function (Y, NAME) {

/**
 * JavaScript for form editing relativedate conditions.
 *
 * @module moodle-availability_relativedate-form
 */
M.availability_relativedate = M.availability_relativedate || {};

// Class M.availability_relativedate.form @extends M.core_availability.plugin.
M.availability_relativedate.form = Y.Object(M.core_availability.plugin);

// Time fields available for selection.
M.availability_relativedate.form.timeFields = null;

// Start field available for selection.
M.availability_relativedate.form.startFields = null;

// A section or a module.
M.availability_relativedate.form.isSection = null;

// Optional warnings that can be displayed.
M.availability_relativedate.form.warningStrings = null;


/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {array} timeFields Collection of time fields
 * @param {array} startFields Collection of start fields
 * @param {boolean} isSection Is this a section
 * @param {array} warningStrings Collection of warning strings
 */
M.availability_relativedate.form.initInner = function(timeFields, startFields, isSection, warningStrings) {
    this.timeFields = timeFields;
    this.startFields = startFields;
    this.isSection = isSection;
    this.warningStrings = warningStrings;
};

M.availability_relativedate.form.getNode = function(json) {
    var html = '<span class="availability-relativedate">';
    var fieldInfo;
    var i = 0;

    for (i = 0; i < this.warningStrings.length; i++) {
        html += '<div class="alert alert-warning alert-block fade in " role="alert">' + this.warningStrings[i] + '</div>';
    }
    html += '<label><select name="relativenumber">';
    for (i = 1; i < 52; i++) {
        html += '<option value="' + i + '">' + i + '</option>';
    }

    html += '</select></label> ';
    html += '<label><select name="relativednw">';
    for (i = 0; i < this.timeFields.length; i++) {
        fieldInfo = this.timeFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label> ';
    html += '<label><select name="relativestart">';

    for (i = 0; i < this.startFields.length; i++) {
        fieldInfo = this.startFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values if specified.
    i = 1;
    if (json.n !== undefined) {
        i = json.n;
    }
    node.one('select[name=relativenumber]').set('value', i);

    i = 2;
    if (json.d !== undefined) {
        i = json.d;
    }
    node.one('select[name=relativednw]').set('value', i);

    i = 1;
    if (json.s !== undefined) {
        i = json.s;
    }
    node.one('select[name=relativestart]').set('value', i);

    // Add event handlers (first time only).
    if (!M.availability_relativedate.form.addedEvents) {
        M.availability_relativedate.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_relativedate select');
    }

    return node;
};

M.availability_relativedate.form.fillValue = function(value, node) {
    value.n = node.one('select[name=relativenumber]').get('value');
    value.d = node.one('select[name=relativednw]').get('value');
    value.s = node.one('select[name=relativestart]').get('value');
};

M.availability_relativedate.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);
};

}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
