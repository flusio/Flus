export default function (l10nKey) {
    const l10nValue = window.jsConfiguration.l10n[l10nKey];
    if (l10nValue) {
        return l10nValue;
    } else {
        return l10nKey;
    }
};
