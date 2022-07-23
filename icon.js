liteSchemeIcon = document.querySelector('link#psi-black');
darkSchemeIcon = document.querySelector('link#psi-white');

matcher = window.matchMedia('(prefers-color-scheme: dark)');
matcher.addListener(onUpdate);
onUpdate();

function onUpdate() {
    if (matcher.matches) {
        liteSchemeIcon.remove();
        document.head.append(darkSchemeIcon);
    } 
    else {
        document.head.append(liteSchemeIcon);
        darkSchemeIcon.remove();
    }
}