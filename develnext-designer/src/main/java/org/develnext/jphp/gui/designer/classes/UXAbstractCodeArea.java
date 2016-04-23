package org.develnext.jphp.gui.designer.classes;

import org.develnext.jphp.ext.javafx.classes.UXControl;
import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import org.develnext.jphp.gui.designer.editor.syntax.AbstractCodeArea;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.*;
import php.runtime.env.Environment;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Abstract
@Namespace(GuiDesignerExtension.NS)
public class UXAbstractCodeArea<T extends AbstractCodeArea> extends UXControl<AbstractCodeArea> {
    interface WrappedInterface {
        @Property int tabSize();
        @Property boolean showGutter();
    }

    public UXAbstractCodeArea(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXAbstractCodeArea(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    public AbstractCodeArea getWrappedObject() {
        return super.getWrappedObject();
    }

    @Getter
    public String getSelectedText() {
        return getWrappedObject().getSelectedText();
    }

    @Setter
    public void setSelectedText(String value) {
        getWrappedObject().replaceSelection(value);
    }

    @Getter
    public String getText() {
        return getWrappedObject().getText();
    }

    @Setter
    public void setText(String text) {
        getWrappedObject().setText(text);
    }

    @Getter
    public boolean getEditable() {
        return getWrappedObject().isEditable();
    }

    @Setter
    public void setEditable(boolean value) {
        getWrappedObject().setEditable(value);
    }

    @Getter
    public int getCaretOffset() {
        return getWrappedObject().getCaretColumn();
    }

    @Getter
    public int getCaretLine() {
        return -1; // TODO FIX;
    }

    @Getter
    public int getCaretPosition() {
        return getWrappedObject().getCaretPosition();
    }

    @Setter
    public void setCaretPosition(int value) {
        getWrappedObject().positionCaret(value);
    }

    @Signature
    public void undo() {
        getWrappedObject().undo();
    }

    @Signature
    public void redo() {
        getWrappedObject().redo();
    }

    @Signature
    public void cut() {
        getWrappedObject().cut();
    }

    @Signature
    public void copy() {
        getWrappedObject().copy();
    }

    @Signature
    public void paste() {
        getWrappedObject().paste();
    }

    @Signature
    public boolean canUndo() {
        return getWrappedObject().isUndoAvailable();
    }

    @Signature
    public boolean canRedo() {
        return getWrappedObject().isRedoAvailable();
    }

    @Signature
    public void jumpToLine(int line, int pos) {
        getWrappedObject().positionCaret(getWrappedObject().position(line, pos).toOffset());
    }

    @Signature
    public void insertToCaret(String text) {
        getWrappedObject().insertText(getWrappedObject().getCaretPosition(), text);
    }

    @Signature
    public void select(int position, int length) {
        getWrappedObject().selectRange(position, length);
    }

    @Signature
    public void selectAll() {
        getWrappedObject().selectAll();
    }
}
