package org.develnext.jphp.gui.designer.classes;

import javafx.scene.control.TreeView;
import org.develnext.jphp.ext.javafx.classes.UXTreeView;
import org.develnext.jphp.gui.designer.GuiDesignerExtension;
import org.develnext.jphp.gui.designer.editor.tree.AbstractDirectoryTreeSource;
import org.develnext.jphp.gui.designer.editor.tree.DirectoryTreeView;
import org.develnext.jphp.gui.designer.editor.tree.FileDirectoryTreeSource;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Namespace;
import php.runtime.annotation.Reflection.Signature;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

import java.io.File;

@Namespace(GuiDesignerExtension.NS)
public class UXDirectoryTreeView extends UXTreeView {
    interface WrappedInterface {
        @Reflection.Property @Reflection.Nullable AbstractDirectoryTreeSource treeSource();
    }

    public UXDirectoryTreeView(Environment env, TreeView wrappedObject) {
        super(env, wrappedObject);
    }

    public UXDirectoryTreeView(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Signature
    public void __construct() {
        __wrappedObject = new DirectoryTreeView();
    }

    @Signature
    public void __construct(AbstractDirectoryTreeSource source) {
        __wrappedObject = new DirectoryTreeView(source);
    }
}
