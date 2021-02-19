import React from 'react';
import ReactDOM from 'react-dom';
import {applyMiddleware, createStore, compose} from "redux";
import thunk from "redux-thunk";

import reducer from "./app/store/reducer";
import Wrapper from "./app/components/Wrapper";
import {Provider} from "react-redux";

const composeEnchancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const store = createStore(
    reducer,
    composeEnchancers(applyMiddleware(thunk)),
);

ReactDOM.render(<Provider store={store}><Wrapper/></Provider>, document.getElementById('app'));