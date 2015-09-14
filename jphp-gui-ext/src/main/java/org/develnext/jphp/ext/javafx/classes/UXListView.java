package org.develnext.jphp.ext.javafx.classes;

import javafx.collections.ObservableList;
import javafx.event.EventHandler;
import javafx.geometry.Orientation;
import javafx.scene.Node;
import javafx.scene.SnapshotParameters;
import javafx.scene.control.*;
import javafx.scene.image.ImageView;
import javafx.scene.input.*;
import javafx.scene.paint.Color;
import javafx.util.Callback;
import org.develnext.jphp.ext.javafx.JavaFXExtension;
import php.runtime.annotation.Reflection;
import php.runtime.annotation.Reflection.*;
import php.runtime.env.Environment;
import php.runtime.invoke.Invoker;
import php.runtime.reflection.ClassEntity;

import java.util.ArrayList;
import java.util.List;

@Reflection.Name(JavaFXExtension.NS + "UXListView")
public class UXListView extends UXControl<ListView> {
    interface WrappedInterface {
        @Property boolean editable();
        @Property double fixedCellSize();
        @Property @Nullable Node placeholder();
        @Property Orientation orientation();
        @Property ObservableList items();
        @Property int editingIndex();

        void scrollTo(int index);
        void edit(int index);
    }

    public UXListView(Environment env, ListView wrappedObject) {
        super(env, wrappedObject);
    }

    public UXListView(Environment env, ClassEntity clazz) {
        super(env, clazz);
    }

    @Signature
    public void __construct() {
        __wrappedObject = new ListView<>();

    }

    @Getter
    public boolean getMultipleSelection() {
        return getWrappedObject().getSelectionModel().getSelectionMode() == SelectionMode.MULTIPLE;
    }

    @Setter
    public void setMultipleSelection(boolean value) {
        getWrappedObject().getSelectionModel().setSelectionMode(value ? SelectionMode.MULTIPLE : SelectionMode.SINGLE);
    }

    @Getter
    public List<Integer> getSelectedIndexes() {
        return getWrappedObject().getSelectionModel().getSelectedIndices();
    }

    @Setter
    public void setSelectedIndexes(int[] indexes) {
        MultipleSelectionModel selectionModel = getWrappedObject().getSelectionModel();

        selectionModel.clearSelection();

        for (int index : indexes) {
            selectionModel.select(index);
        }
    }

    @Getter
    public int getSelectedIndex() {
        return getWrappedObject().getSelectionModel().getSelectedIndex();
    }

    @Setter
    public void setSelectedIndex(int index) {
        MultipleSelectionModel selectionModel = getWrappedObject().getSelectionModel();

        selectionModel.clearSelection();
        selectionModel.select(index);
    }

    @Getter
    public Object getFocusedItem() {
        return getWrappedObject().getFocusModel().getFocusedItem();
    }

    @Getter
    public List<Object> getSelectedItems() {
        return getWrappedObject().getSelectionModel().getSelectedItems();
    }

    @Getter
    public Object getFocusedIndex() {
        return getWrappedObject().getFocusModel().getFocusedIndex();
    }

    @Setter
    public void setFocusedIndex(int index) {
        getWrappedObject().getFocusModel().focus(index);
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void setCellFactory(final Environment env, @Nullable final Invoker invoker) {
        if (invoker == null) {
            getWrappedObject().setCellFactory(null);
            return;
        }

        getWrappedObject().setCellFactory(new Callback<ListView, ListCell>() {
            @Override
            public ListCell call(ListView param) {
                return new ListCell() {
                    @Override
                    protected void updateItem(Object item, boolean empty) {
                        super.updateItem(item, empty);

                        if (empty) {
                            setText(null);
                            setGraphic(null);
                        } else {
                            invoker.callAny(new UXListCell(env, this), item, empty);
                        }
                    }
                };
            }
        });
    }

    @Signature
    public void setDraggableCellFactory(final Environment env, @Nullable final Invoker invoker, @Nullable final Invoker dragDone) {
        if (invoker == null) {
            getWrappedObject().setCellFactory(null);
            return;
        }

        getWrappedObject().setCellFactory(new Callback<ListView, ListCell>() {
            @Override
            public ListCell call(ListView param) {
                return new DragListCell(dragDone) {
                    @Override
                    protected void updateItem(Object item, boolean empty) {
                        super.updateItem(item, empty);

                        if (empty) {
                            setText(null);
                            setGraphic(null);
                        } else {
                            invoker.callAny(new UXListCell(env, this), item, empty);
                        }
                    }
                };
            }
        });
    }

    @Signature
    @SuppressWarnings("unchecked")
    public void update() {
        ObservableList items = getWrappedObject().getItems();

        getWrappedObject().setItems(null);
        getWrappedObject().setItems(items);
    }


    static class DragListCell extends ListCell {
        private final ImageView imageView = new ImageView();
        private final Invoker dragDone;


        public DragListCell(final Invoker dragDone) {
            this.dragDone = dragDone;

            final ListCell thisCell = this;

            setOnDragDetected(new EventHandler<MouseEvent>() {
                @Override
                public void handle(MouseEvent event) {
                    if (DragListCell.this.getItem() == null) {
                        return;
                    }

                    Dragboard dragboard = DragListCell.this.startDragAndDrop(TransferMode.MOVE);

                    ClipboardContent content = new ClipboardContent();
                    content.putString(String.valueOf(getListView().getSelectionModel().getSelectedIndex()));

                    dragboard.setContent(content);

                    SnapshotParameters snapParams = new SnapshotParameters();
                    snapParams.setFill(Color.TRANSPARENT);

                    imageView.setImage(DragListCell.this.getGraphic().snapshot(snapParams, null));
                    imageView.setStyle("-fx-border-color: silver; -fx-border-width: 1px;");

                    event.consume();
                }
            });

            setOnDragOver(new EventHandler<DragEvent>() {
                @Override
                public void handle(DragEvent event) {
                    if (event.getGestureSource() != thisCell &&
                            event.getDragboard().hasString()) {
                        event.acceptTransferModes(TransferMode.MOVE);
                    }

                    event.consume();
                }
            });

            setOnDragEntered(new EventHandler<DragEvent>() {
                @Override
                public void handle(DragEvent event) {
                    if (event.getGestureSource() != thisCell &&
                            event.getDragboard().hasString()) {
                        DragListCell.this.setOpacity(0.3);
                    }
                }
            });

            setOnDragExited(new EventHandler<DragEvent>() {
                @Override
                public void handle(DragEvent event) {
                    if (event.getGestureSource() != thisCell &&
                            event.getDragboard().hasString()) {
                        DragListCell.this.setOpacity(1);
                    }
                }
            });

            setOnDragDropped(new EventHandler<DragEvent>() {
                @Override
                public void handle(DragEvent event) {
                    if (DragListCell.this.getItem() == null) {
                        return;
                    }

                    Dragboard db = event.getDragboard();
                    boolean success = false;

                    if (db.hasString()) {
                        ObservableList items = DragListCell.this.getListView().getItems();
                        int draggedIdx = Integer.parseInt(db.getString());
                        int thisIdx = items.indexOf(DragListCell.this.getItem());

                        Object dragged = DragListCell.this.getListView().getItems().get(draggedIdx);

                        if (thisIdx < draggedIdx) {
                            items.add(thisIdx, dragged);
                            items.remove(++draggedIdx);
                        } else {
                            items.add(thisIdx + 1, dragged);
                            items.remove(draggedIdx);
                        }

                        List itemscopy = new ArrayList<>(DragListCell.this.getListView().getItems());
                        DragListCell.this.getListView().getItems().setAll(itemscopy);

                        success = true;

                        if (dragDone != null) {
                            dragDone.callAny(draggedIdx, thisIdx);
                        }
                    }

                    event.setDropCompleted(success);

                    event.consume();
                }
            });

            setOnDragDone(new EventHandler<DragEvent>() {
                @Override
                public void handle(DragEvent dragEvent) {
                    dragEvent.consume();
                }
            });
        }
    }
}
