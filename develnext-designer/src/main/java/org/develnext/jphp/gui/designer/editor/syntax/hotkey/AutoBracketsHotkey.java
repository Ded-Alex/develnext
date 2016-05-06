package org.develnext.jphp.gui.designer.editor.syntax.hotkey;

import javafx.scene.input.KeyCode;
import javafx.scene.input.KeyEvent;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;

public class AutoBracketsHotkey extends AbstractHotkey {
    @Override
    public boolean apply(AbstractCodeArea area, KeyEvent keyEvent) {
        char addClosed = '\0';

        int pos = area.getCaretPosition();
        String ch = area.getText(pos - 1, pos);

        switch (ch) {
            case "{":
                if (keyEvent.getCode() == KeyCode.OPEN_BRACKET) {
                    addClosed = '}';
                }
                break;

            case "[":
                if (keyEvent.getCode() == KeyCode.OPEN_BRACKET) {
                    addClosed = ']';
                }
                break;

            case "(":
                if (keyEvent.getCode().isLetterKey() || keyEvent.getCode().isDigitKey()) {
                    addClosed = ')';
                }

                break;

            case "\"":
                if (keyEvent.getCode() == KeyCode.QUOTE) {
                    if (area.getText().length() >= pos + 1 && area.getText().charAt(pos) == ch.charAt(0)) {
                        area.replaceText(pos, pos + 1, "");
                    } else {
                        addClosed = '"';
                    }
                }

                break;
            case "'":
                if (keyEvent.getCode() == KeyCode.QUOTE) {
                    if (area.getText().length() >= pos + 1 && area.getText().charAt(pos) == ch.charAt(0)) {
                        area.replaceText(pos, pos + 1, "");
                    } else {
                        addClosed = '\'';
                    }
                }

                break;

            case "}":
            case "]":
            case ")":
                if (area.getText().length() >= pos + 1 &&
                        (keyEvent.getCode() == KeyCode.CLOSE_BRACKET || keyEvent.getCode().isLetterKey() || keyEvent.getCode().isDigitKey())) {
                    if (area.getText().charAt(pos) == ch.charAt(0)) {
                        area.replaceText(pos, pos + 1, "");
                    }
                }
        }

        if (addClosed != '\0') {
            area.replaceSelection(String.valueOf(addClosed));
            area.moveTo(pos);
            return true;
        } else {
            return false;
        }
    }

    @Override
    public KeyCode getDefaultKeyCode() {
        return null;
    }
}
