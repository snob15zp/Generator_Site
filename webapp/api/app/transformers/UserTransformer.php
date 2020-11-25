<?php


class UserTransformer implements Transformer
{

    function transform($data): array
    {
        return [
            'name'=> $data['userProfile']['name'],
            'token'=>$data['session']['token'],
        ];
    }
}
