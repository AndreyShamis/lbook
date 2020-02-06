<?php

namespace App\Form;

use App\Entity\TestFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Filter Name',
            ))
            ->add('suiteUuid', null, array(
                'label' => 'Suite UUID',
            ))
            ->add('testList',TextareaType::class, array(
                'attr' => array('class' => 'filterTestList', 'rows' => '10'),
                'label' => 'Test/s',
            ))
            ->add('testingLevel')
            ->add('projectName')
            ->add('cluster')
            ->add('chip', null, array('required' => false))
            ->add('platform', null, array('required' => false))
            ->add('executionMode', null, array('required' => false))
            ->add('branchName', null, array('required' => false))
//            ->add('enabled')
            ->add('description')
            ->add('defectUrl')
//            ->add('createdAt')
//            ->add('updatedAt')
            ->add('issueContact')
//            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestFilter::class,
        ]);
    }
}
