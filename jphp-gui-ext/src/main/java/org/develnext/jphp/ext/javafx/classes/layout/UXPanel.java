package org.develnext.jphp.ext.javafx.classes.layout;

import javafx.scene.paint.Color;
import org.develnext.jphp.ext.javafx.JavaFXExtension;
import org.develnext.jphp.ext.javafx.support.control.Panel;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.Nullable;
import php.runtime.annotation.Reflection.Property;
import php.runtime.env.Environment;
import php.runtime.reflection.ClassEntity;

@Reflection.Name(JavaFXExtension.NS + "layout\\UXPanel")
public class UXPanel extends UXAnchorPane<Panel> {
    interface WrappedInterface {
        @Property @Nullable Color borderColor();
        @Property double borderWidth();
        @Property double borderRadius();
        @Property String borderStyle();
    }

    public UXPanel(Environment env, Panel wrappedObject) {
        super(env, wrappedObject);
    }

    public UXPanel(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    public void __construct() {
        __wrappedObject = new Panel();
    }


}
