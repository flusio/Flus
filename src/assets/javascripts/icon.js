export default function (iconName) {
    const icon = window.jsConfiguration.icons[iconName];
    if (icon) {
        return icon;
    } else {
        return '';
    }
};
