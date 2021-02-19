import * as actionTypes from './actionTypes';

const initialState = {};

const updateState = (state, payload) => {
    return {
        ...state,
        ...payload,
    }
};

export default (state = initialState, action) => {
    switch (action.type) {
        default:
            return state;
    }
}