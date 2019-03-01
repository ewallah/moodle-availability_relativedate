YUI.add('moodle-availability_relativedate-form', function (Y, NAME) {

/**
 * JavaScript for form editing relativedate conditions.
 *
 * @module moodle-availability_relativedate-form
 */
M.availability_relativedate = M.availability_relativedate || {};

// Class M.availability_relativedate.form @extends M.core_availability.plugin.
M.availability_relativedate.form = Y.Object(M.core_availability.plugin);

// Options available for selection.
M.availability_relativedate.form.relativedates = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {boolean} completed Is completed or not
 */
M.availability_relativedate.form.initInner = function(timeFields, startFields) {
    this.timeFields = timeFields;
    this.startFields = startFields;
};

M.availability_relativedate.form.getNode = function(json) {
    // Create HTML structure.
    var strings = M.str.availability_relativedate;
    var html = '<span class="availability-relativedate"><label><select name="relativenumber">';
    for (var i = 1; i < 52; i++) {
        html += '<option value="' + i + '">' + i + '</option>';
    }
    
    html += '</select></label> ';
    html += '<label><select name="relativednw">';
    for (var i = 0; i < this.timeFields.length; i++) {
        fieldInfo = this.timeFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label> ';
    html += '<label><select name="relativestart">';
    for (var i = 0; i < this.startFields.length; i++) {
        fieldInfo = this.startFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label>';
    html += '</span>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values if specified.
    var jasonnval = 1;
    if (json.n !== undefined) {
        jasonnval = json.n;
    }
    node.one('select[name=relativenumber]').set('value', jasonnval);

    var jasondval = 2;
    if (json.d !== undefined) {
        jasondval = json.d;
    }
    node.one('select[name=relativednw]').set('value', jasondval);

    var jasonsval = 1;
    if (json.s !== undefined) {
        jasonsval = json.s;
    }
    node.one('select[name=relativestart]').set('value', jasonsval);
    
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
