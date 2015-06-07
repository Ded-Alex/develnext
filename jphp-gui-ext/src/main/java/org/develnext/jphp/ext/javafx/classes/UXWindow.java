package org.develnext.jphp.ext.javafx.classes;

import javafx.collections.ObservableList;
import javafx.event.Event;
import javafx.scene.Node;
import javafx.scene.layout.Pane;
import javafx.stage.Stage;
import javafx.stage.StageStyle;
import javafx.stage.Window;
import org.develnext.jphp.ext.javafx.JavaFXExtension;
import org.develnext.jphp.ext.javafx.support.EventProvider;
import php.runtime.Memory;
import php.runtime.annotation.Reflection.*;
import php.runtime.env.Environment;
import php.runtime.invoke.Invoker;
import php.runtime.lang.BaseWrapper;
import php.runtime.reflection.ClassEntity;

@Abstract
@Name(JavaFXExtension.NS + "UXWindow")
public class UXWindow<T extends Window> extends BaseWrapper<Window> {
    interface WrappedInterface {
        @Property double x();
        @Property double y();
        @Property double height();
        @Property double width();
        @Property double opacity();

        @Property boolean focused();
        @Property boolean showing();

        void hide();
        void sizeToScene();

        void centerOnScreen();
    }

    public UXWindow(Environment env, T wrappedObject) {
        super(env, wrappedObject);
    }

    public UXWindow(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Override
    @SuppressWarnings("unchecked")
    public T getWrappedObject() {
        return (T) super.getWrappedObject();
    }

    @Signature
    public void __construct() {
        __wrappedObject = new Stage();
    }

    @Signature
    public void __construct(StageStyle style) {
        __wrappedObject = new Stage(style);
    }

    @Getter
    protected double[] getSize() {
        return new double[] { getWrappedObject().getWidth(), getWrappedObject().getHeight() };
    }

    @Setter
    protected void setSize(double[] size) {
        if (size.length >= 2) {
            getWrappedObject().setWidth(size[0]);
            getWrappedObject().setHeight(size[1]);
        }
    }

    @Getter
    protected Pane getLayout() {
        return getWrappedObject().getScene() == null ? null : (Pane) getWrappedObject().getScene().getRoot();
    }

    @Setter
    protected void setLayout(Pane pane) {
        if (getWrappedObject().getScene() == null) {
            throw new IllegalStateException("Unable to set layout");
        }

        getWrappedObject().getScene().setRoot(pane);
        getWrappedObject().sizeToScene();
    }


    @Getter
    public ObservableList<Node> getChildren() {
        Pane root = (Pane) getWrappedObject().getScene().getRoot();
        return root == null ? null : root.getChildren();
    }

    @Signature
    public void add(Node node) {
        ObservableList<Node> children = getChildren();

        if (children != null) {
            children.add(node);
        } else {
            throw new IllegalStateException("Unable to add node");
        }
    }

    @Signature
    public boolean remove(Node node) {
        ObservableList<Node> children = getChildren();

        if (children != null) {
            return children.remove(node);
        } else {
            throw new IllegalStateException("Unable to remove node");
        }
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void on(String event, Invoker invoker, String group) {
        Object target = getWrappedObject();
        EventProvider eventProvider = EventProvider.get(target, event);

        if (eventProvider == null) {
            eventProvider = EventProvider.get(target = getWrappedObject().getScene().getRoot(), event);
        }

        if (eventProvider != null) {
            eventProvider.on(target, event, group, invoker);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public void on(String event, Invoker invoker) {
        on(event, invoker, "general");
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void off(String event, @Nullable String group) {
        Object target = getWrappedObject();
        EventProvider eventProvider = EventProvider.get(target, event);

        if (eventProvider == null) {
            eventProvider = EventProvider.get(target = getWrappedObject().getScene().getRoot(), event);
        }

        if (eventProvider != null) {
            eventProvider.off(target, event, group);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public void off(String event) {
        off(event, null);
    }

    @Signature
    public void trigger(String event, @Nullable Event e) {
        Object target = getWrappedObject();
        EventProvider eventProvider = EventProvider.get(target, event);

        if (eventProvider == null) {
            eventProvider = EventProvider.get(target = getWrappedObject().getScene().getRoot(), event);
        }

        if (eventProvider != null) {
            eventProvider.trigger(target, event, e);
        } else {
            throw new IllegalArgumentException("Unable to find the '"+event+"' event type");
        }
    }

    @Signature
    public Memory __get(Environment env, String name) {
        Node node = getWrappedObject().getScene().lookup("#" + name);
        return Memory.wrap(env, node);
    }

    @Signature
    public boolean __isset(String name) {
        return getWrappedObject().getScene().lookup("#" + name) != null;
    }
}
