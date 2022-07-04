Object.defineProperty(Object.prototype, "define", {
    configurable: true,
    enumerable: false,
    writable: true,
    value: function (name, value) {
        if (Object[name]) {
            delete Object[name];
        }
        Object.defineProperty(this, name, {
            configurable: true,
            enumerable: false,
            writable: true,
            value: value
        });
        return this;
    }
});
String.prototype.define('toTitleCase', function() {
    return this.replace(/\w\S*/g, function (txt) {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
});

String.prototype.define('escapeHTML', function() {  
    let replacements = {"<": "&lt;", ">": "&gt;", "&": "&amp;", "'": "&apos;", "\"": "&quot;"};                      
    return this.replace(/[<>&'"]/g, function(character) {  
        return replacements[character];  
    }); 
});
